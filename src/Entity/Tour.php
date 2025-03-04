<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use AppBundle\Api\Dto\TourInput;
use AppBundle\Entity\Sylius\Order;
use AppBundle\Entity\Task\CollectionInterface as TaskCollectionInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *   collectionOperations={
 *     "post"={
 *       "method"="POST",
 *       "input"=TourInput::class,
 *       "security"="is_granted('ROLE_DISPATCHER')"
 *     }
 *   },
 *   itemOperations={
 *     "get"={
 *       "method"="GET",
 *       "security"="is_granted('ROLE_DISPATCHER')"
 *     },
 *     "put"={
 *       "method"="PUT",
 *       "input"=TourInput::class,
 *       "security"="is_granted('ROLE_DISPATCHER')"
 *     }
 *   },
 *   attributes={
 *     "denormalization_context"={"groups"={"tour"}},
 *     "normalization_context"={"groups"={"task_collection", "task", "tour"}}
 *   }
 * )
 */
class Tour extends TaskCollection implements TaskCollectionInterface
{
    protected $id;

    /**
     * @var string
     * @Groups({"tour", "task"})
     */
    protected $name;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function addTask(Task $task, $position = null)
    {
        $task->setTour($this);

        return parent::addTask($task, $position);
    }

    public function removeTask(Task $task)
    {
        $task->setTour(null);
        parent::removeTask($task);
    }

    public function getTaskPosition(Task $task)
    {
        foreach ($this->getItems() as $item) {
            if ($item->getTask() === $task) {
                return $item->getPosition();
            }
        }

        return 0;
    }
}
