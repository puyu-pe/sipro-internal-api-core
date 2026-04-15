<?php

declare(strict_types=1);

namespace PuyuPe\SiproInternalApiCore\Tests\Contracts;

use PHPUnit\Framework\TestCase;
use PuyuPe\SiproInternalApiCore\Contracts\Dto\ProvisionPayloadDTO;

final class ProvisionPayloadDTOTest extends TestCase
{
    public function testFromArrayBuildsNestedPayload(): void
    {
        $payload = [
            'project' => [
                'name' => 'Acme Suite',
                'code' => 'ACME',
                'description' => 'Suite principal',
                'billingCycle' => 'monthly',
                'priceAgreed' => 199.9,
                'startDate' => '2026-03-25',
                'renewalDate' => '2027-03-25',
                'execStatus' => 'active',
                'isActive' => true,
                'accessUrlCustom' => 'https://acme.sipro.app',
                'accessUrls' => [
                    'app' => 'https://acme.sipro.app',
                    'api' => 'https://api.acme.sipro.app',
                ],
                'resolveKey' => 'acme-app-001',
                'logo' => null,
                'address' => 'Av. Demo 123',
                'phone' => '+51 1 5555555',
                'email' => 'admin@acme.pe',
                'ubigeo' => '150101',
                'latitud' => -12.0464,
                'longitud' => -77.0428,
                'color' => '#004c97',
                'notes' => 'Cliente migrado',
            ],
            'client' => [
                'ruc' => '20123456789',
                'businessName' => 'Acme SAC',
                'tradeName' => 'Acme',
            ],
            'services' => [
                [
                    'key' => 'billing',
                    'externalId' => 'srv-01',
                    'code' => 'BILL',
                    'name' => 'Facturacion',
                    'description' => 'Modulo de facturacion',
                    'priceList' => 99.0,
                    'defaultBillingCycle' => 'monthly',
                    'type' => 'core',
                    'accessUrl' => 'https://acme.sipro.app/billing',
                    'logo' => null,
                    'credentials' => [
                        [
                            'name' => 'Admin Acme',
                            'username' => 'admin',
                            'email' => 'admin@acme.pe',
                            'role' => 'owner',
                            'initialPassword' => 'Temporal123!',
                            'mustChangePassword' => true,
                        ],
                    ],
                    'modules' => [
                        [
                            'id' => 10,
                            'externalId' => 'mod-01',
                            'name' => 'Ventas',
                            'description' => 'Ventas y cotizaciones',
                            'price' => 20.0,
                            'isUnlimited' => false,
                            'customPrice' => null,
                            'quantity' => 5,
                        ],
                    ],
                ],
            ],
            'metadata' => [
                'source' => 'control-plane',
                'priority' => 'high',
            ],
        ];

        $dto = ProvisionPayloadDTO::fromArray($payload);

        self::assertSame($payload, $dto->toArray());
    }
}
