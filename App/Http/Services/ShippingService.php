<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ShippingService
{
    protected $jsonData;

    public function __construct($jsonData)
    {
        $this->jsonData = $jsonData;
    }

    // Task 1: PLS-0001
    public function getCheapestDirectSailing($originPort, $destinationPort)
    {
        $directSailings = $this->filterDirectSailings($originPort, $destinationPort);
        $cheapestSailing = $this->findCheapestSailing($directSailings);
        
        return $cheapestSailing;
    }

    // Task 2: WRT-0002
    public function getCheapestSailing($originPort, $destinationPort)
    {
        $allSailings = $this->getAllSailings($originPort, $destinationPort);
        $cheapestSailing = $this->findCheapestSailing($allSailings);
        
        return $cheapestSailing;
    }

    // Bonus Task 3: TST-0003
    public function getFastestSailing($originPort, $destinationPort)
    {
        $allSailings = $this->getAllSailings($originPort, $destinationPort);
        $fastestSailing = $this->findFastestSailing($allSailings);

        return $fastestSailing;        
    }

    // Helper methods for retrieving all required data
    protected function filterDirectSailings($originPort, $destinationPort)
    {
        return collect($this->jsonData['sailings'])->filter(function ($sailing) use ($originPort, $destinationPort) {
            return $sailing['origin_port'] === $originPort && $sailing['destination_port'] === $destinationPort;
        });
    }

    protected function getAllSailings($originPort, $destinationPort)
    {
        return collect($this->jsonData['sailings'])->filter(function ($sailing) use ($originPort, $destinationPort) {
            return $sailing['origin_port'] === $originPort && $sailing['destination_port'] === $destinationPort;
        });
    }

    protected function findCheapestSailing(Collection $sailings)
    {
        $cheapestSailing = null;
        $cheapestRate = PHP_FLOAT_MAX;

        foreach ($sailings as $sailing) {
            $rateData = $this->findRateData($sailing['sailing_code']);
            $rate = $this->convertRateToEUR($rateData['rate'], $rateData['rate_currency'], $sailing['departure_date']);

            if ($rate < $cheapestRate) {
                $cheapestRate = $rate;
                $cheapestSailing = $sailing;
                $cheapestSailing['rate'] = number_format($rate, 2);
                $cheapestSailing['rate_currency'] = 'EUR';
            }
        }

        return $cheapestSailing;
    }

    protected function findFastestSailing(Collection $sailings) {
        $fastestSailings = [];

        foreach ($sailings as $sailing) {
            $sailingLegs = $this->findIndirectSailings($sailing['origin_port'], $sailing['destination_port']);
            $sailingLegs[] = $sailing;

            $totalDuration = $this->calculateTotalDuration($sailingLegs);

            if (empty($fastestSailings) || $totalDuration < $fastestSailings['duration']) {
                $fastestSailings = [
                    'sailings' => $sailingLegs,
                    'duration' => $totalDuration
                ];
            }
        }

        return $fastestSailings['sailings'];
    }

    protected function findRateData($sailingCode)
    {
        return collect($this->jsonData['rates'])->firstWhere('sailing_code', $sailingCode);
    }

    protected function convertRateToEUR($rate, $currency, $date)
    {
        $exchangeRate = $this->jsonData['exchange_rates'][$date][$currency];
        return $currency === 'EUR' ? $rate : ($rate / $exchangeRate);
    }

    protected function findIndirectSailings($originPort, $destinationPort)
    {
        return collect($this->jsonData['sailings'])->filter(function ($sailing) use ($originPort, $destinationPort) {
            return $sailing['origin_port'] === $originPort && $sailing['destination_port'] !== $destinationPort;
        })->toArray();
    }

    protected function calculateTotalDuration($sailings)
    {
        $departureDates = collect($sailings)->pluck('departure_date');
        $arrivalDates = collect($sailings)->pluck('arrival_date');

        $departureTimestamps = array_map('strtotime', $departureDates);
        $arrivalTimestamps = array_map('strtotime', $arrivalDates);

        $totalDuration = max($arrivalTimestamps) - min($departureTimestamps);
        return $totalDuration;
    }
}
