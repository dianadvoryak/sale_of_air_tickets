<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

use OpenApi\Attributes as OA;

class TravelController extends AbstractController
{
    #[Route(path: '/api/v1/calculation', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/calculation',
        description: 'Calculate discount based on base price, birth date, start date, and payment date.',
        summary: 'Calculate discount',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'base_price', type: 'number', example: 10000),
                    new OA\Property(property: 'birth_date', type: 'string', format: 'date', example: '2010-05-15'),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2022-07-01'),
                    new OA\Property(property: 'payment_date', type: 'string', format: 'date', example: '2021-11-30')
                ],
                type: 'object'
            )
        ),
    )]
    public function costCalculation(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $basePrice = $data['base_price'];
        $birthDate = new \DateTime($data['birth_date']);
        $startDate = new \DateTime($data['start_date']);
        $paymentDate = new \DateTime($data['payment_date']);

        // Рассчет детской скидки
        $ageAtStart = $startDate->diff($birthDate)->y;
        $discountedPrice = $basePrice;

        if ($ageAtStart < 3) {
            // No discount for children under 3
        } elseif ($ageAtStart < 6) {
            $discountedPrice *= 0.2;
        } elseif ($ageAtStart < 12) {
            $discountedPrice -= min($basePrice * 0.3, 4500);
        } elseif ($ageAtStart < 18) {
            $discountedPrice *= 0.9;
        }

        // Рассчет скидки за раннее бронирование
        $earlyBirdDiscount = 0;
        $maxDiscount = 1500;

        $startMonthDay = (int)$startDate->format('md');
        $paymentMonth = (int)$paymentDate->format('m');
        $paymentYear = (int)$paymentDate->format('Y');
        $startYear = (int)$startDate->format('Y');

        if ($startMonthDay >= 401 && $startMonthDay <= 930) {
            if ($paymentMonth <= 11) {
                $earlyBirdDiscount = 0.07;
            } elseif ($paymentMonth == 12) {
                $earlyBirdDiscount = 0.05;
            } elseif ($paymentMonth == 1 && $paymentYear == $startYear) {
                $earlyBirdDiscount = 0.03;
            }
        } elseif ($startMonthDay >= 1001 || $startMonthDay <= 114) { // 1 октября - 14 января
            if ($paymentMonth <= 3) {
                $earlyBirdDiscount = 0.07;
            } elseif ($paymentMonth == 4) {
                $earlyBirdDiscount = 0.05;
            } elseif ($paymentMonth == 5) {
                $earlyBirdDiscount = 0.03;
            }
        } elseif ($startMonthDay >= 115 && $startMonthDay <= 331) { // 15 января - 31 марта
            if ($paymentMonth <= 8) {
                $earlyBirdDiscount = 0.07;
            } elseif ($paymentMonth == 9) {
                $earlyBirdDiscount = 0.05;
            } elseif ($paymentMonth == 10) {
                $earlyBirdDiscount = 0.03;
            }
        }
        $earlyBirdDiscountAmount = min($discountedPrice * $earlyBirdDiscount, $maxDiscount);
        $finalPrice = $discountedPrice - $earlyBirdDiscountAmount;

        return new Response('Final price: ' . $finalPrice . ' RUB');

    }
}