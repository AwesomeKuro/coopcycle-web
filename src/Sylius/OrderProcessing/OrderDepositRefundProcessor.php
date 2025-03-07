<?php

namespace AppBundle\Sylius\OrderProcessing;

use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Sylius\Component\Order\Model\Adjustment;
use AppBundle\Sylius\Order\AdjustmentInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderDepositRefundProcessor implements OrderProcessorInterface
{
    const LOOPEAT_PROCESSING_FEE = 200;

    public function __construct(
        AdjustmentFactoryInterface $adjustmentFactory,
        TranslatorInterface $translator)
    {
        $this->adjustmentFactory = $adjustmentFactory;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(BaseOrderInterface $order): void
    {
        $order->removeAdjustmentsRecursively(AdjustmentInterface::REUSABLE_PACKAGING_ADJUSTMENT);

        // For the moment, not supported on hubs
        if (!$order->hasVendor() || $order->isMultiVendor()) {
            return;
        }

        $restaurant = $order->getRestaurant();

        if (!$restaurant->isDepositRefundEnabled() && !$restaurant->isLoopeatEnabled() && !$restaurant->isDabbaEnabled()) {
            return;
        }

        if ($restaurant->isDepositRefundOptin()) {
            if (!$order->isReusablePackagingEnabled()) {
                return;
            }
        }

        $totalAmount = 0;
        foreach ($order->getItems() as $item) {

            $product = $item->getVariant()->getProduct();

            if ($product->isReusablePackagingEnabled()) {

                if (!$product->hasReusablePackagings()) {
                    continue;
                }

                $reusablePackagings = $product->getReusablePackagings();

                foreach ($reusablePackagings as $reusablePackaging) {

                    $units = ceil($reusablePackaging->getUnits() * $item->getQuantity());

                    $pkg = $reusablePackaging->getReusablePackaging();

                    $label = $pkg->getAdjustmentLabel($this->translator, $units);
                    $amount = $pkg->getPrice() * $units;

                    $item->addAdjustment($this->adjustmentFactory->createWithData(
                        AdjustmentInterface::REUSABLE_PACKAGING_ADJUSTMENT,
                        $label,
                        $amount,
                        $neutral = true
                    ));

                    $totalAmount += $amount;
                }
            }
        }

        // Collect an additional fee for LoopEat, *PER ORDER*
        // https://github.com/coopcycle/coopcycle-web/issues/2284
        if ($restaurant->isLoopeatEnabled()) {
            $order->addAdjustment($this->adjustmentFactory->createWithData(
                AdjustmentInterface::REUSABLE_PACKAGING_ADJUSTMENT,
                $this->translator->trans('order.adjustment_type.reusable_packaging.loopeat'),
                self::LOOPEAT_PROCESSING_FEE,
                $neutral = false
            ));
        } else if ($totalAmount > 0) {
            $order->addAdjustment($this->adjustmentFactory->createWithData(
                AdjustmentInterface::REUSABLE_PACKAGING_ADJUSTMENT,
                $this->translator->trans('order.adjustment_type.reusable_packaging'),
                $totalAmount,
                $neutral = false
            ));
        }
    }
}
