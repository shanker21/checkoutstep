<?php
namespace MDC\CheckoutStep\Model\Config\Source;


class DeliverySlot implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Retrieve Custom Option array
     *
     * @return array
     */
    public function toOptionArray()
    {

        $months = $this->lastThreeMonths();
        $resArray=[];
        $counter=-1;
        $totalmonthDays=0;
        foreach ($months as $monthName){
            if($monthName==__('Next 5 delivery dates')) {
                $resArray[] = ['value' => '-1', 'label' => __($monthName)];
            } else {
                // $totalmonthDays=date("n", strtotime('+'.$counter.' month'));
                $resArray[] = ['value' => $counter, 'label' => __($monthName)];
            }
            $counter++;
        }
        return $resArray;
    }


    public function lastThreeMonths() {
        return array(
            __('Next 5 delivery dates'),
            date('F', time()),
            date('F', strtotime('first day of +1 month')),
            date('F', strtotime('first day of +2 month'))
        );
    }
}