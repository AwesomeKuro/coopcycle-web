<?php

namespace AppBundle\MessageHandler;

use AppBundle\Entity\Task;
use AppBundle\Entity\Task\Group as TaskGroup;
use AppBundle\Message\ImportTasks;
use AppBundle\Service\RemotePushNotificationManager;
use AppBundle\Service\SocketIoManager;
use AppBundle\Utils\TaskSpreadsheetParser;
use Doctrine\ORM\EntityManagerInterface;
use Hashids\Hashids;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImportTasksHandler implements MessageHandlerInterface
{
    private $objectManager;
    private $taskImportsFilesystem;
    private $spreadsheetParser;
    private $validator;
    private $socketIoManager;
    private $hashids;
    private $logger;

    public function __construct(
        EntityManagerInterface $objectManager,
        Filesystem $taskImportsFilesystem,
        TaskSpreadsheetParser $spreadsheetParser,
        ValidatorInterface $validator,
        SocketIoManager $socketIoManager,
        string $secret,
        LoggerInterface $logger)
    {
        $this->objectManager = $objectManager;
        $this->taskImportsFilesystem = $taskImportsFilesystem;
        $this->spreadsheetParser = $spreadsheetParser;
        $this->validator = $validator;
        $this->socketIoManager = $socketIoManager;
        $this->logger = $logger;
        $this->hashids = new Hashids($secret, 8);
    }

    public function __invoke(ImportTasks $message)
    {
        RemotePushNotificationManager::disable();

        $decoded = $this->hashids->decode($message->getToken());
        if (count($decoded) !== 1) {
            $this->logger->error(sprintf('Token "%s" could not be decoded', $message->getToken()));
            return;
        }

        $taskGroupId = current($decoded);

        $taskGroup = $this->objectManager
            ->getRepository(TaskGroup::class)
            ->find($taskGroupId);

        if (!$taskGroup) {
            $this->logger->error(sprintf('TaskGroup #%d does not exist', $taskGroupId));
            return;
        }

        // Download file locally
        $tempDir = sys_get_temp_dir();
        $tempnam = tempnam($tempDir, 'coopcycle_task_import');

        if (false === file_put_contents($tempnam, $this->taskImportsFilesystem->read($message->getFilename()))) {
            $this->logger->error('Could not write temp file');
            return;
        }

        try {

            $tasks = $this->spreadsheetParser->parse($tempnam, $message->getDate());

            foreach ($tasks as $task) {
                $violations = $this->validator->validate($task);
                if (count($violations) > 0) {
                    throw new \Exception($violations->get(0)->getMessage());
                }
            }

            $this->logger->info(sprintf('Importing %d tasks…', count($tasks)));

        } catch (\Exception $e) {
            $this->socketIoManager->toAdmins('task_import:failure', [
                ['message' => $e->getMessage()]
            ]);
            unlink($tempnam);
            return;
        }

        foreach ($tasks as $task) {
            $task->setGroup($taskGroup);
            $this->objectManager->persist($task);
        }

        $this->objectManager->flush();

        $this->logger->info(sprintf('Finished importing file %s', $message->getFilename()));

        $this->socketIoManager->toAdmins('task_import:success', ['token' => $message->getToken()]);

        unlink($tempnam);
    }
}
