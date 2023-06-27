<?php

namespace AppBundle\Api\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use AppBundle\Api\Dto\TourInput;
use AppBundle\Entity\Task;
use AppBundle\Entity\Tour;
use AppBundle\Service\DeliveryManager;
use AppBundle\Service\RoutingInterface;
use Doctrine\ORM\EntityManagerInterface;

class TourInputDataTransformer implements DataTransformerInterface
{
    public function __construct(RoutingInterface $routing)
    {
        $this->routing = $routing;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {
        // error_log(serialize($data->tasks));
        // error_log(serialize($to));
        // error_log(serialize($context));
        // dd($context);

        if ($context["operation_type"] == "item" && $context["item_operation_name"] == "put") {
            $tour = $context['object_to_populate'];
            $tour->setName($data->name);
            
            $tour->setTasks($data->tasks);

            foreach ($data->tasks as $task) {
                $task->setTour($tour);
            }

        } else {
            $tour = new Tour();
            
            $tour->setName($data->name);

            foreach ($data->tasks as $task) {
                $tour->addTask($task);
            }
        }

        $coords = array_map(fn ($task) => $task->getAddress()->getGeo(), $tour->getTasks());
        $distance = $this->routing->getDistance(...$coords);

        $tour->setDistance(ceil($distance));

        return $tour;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if ($data instanceof Tour) {
            return false;
        }

        return $to === Tour::class && ($context['input']['class'] ?? null) === TourInput::class;
    }
}

