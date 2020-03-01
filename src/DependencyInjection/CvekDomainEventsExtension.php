<?php
/*
 * This file is part of the Aqua Delivery package.
 *
 * (c) Sergey Logachev <svlogachev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cvek\DomainEventsBundle\DependencyInjection;

use Cvek\DomainEventsBundle\EventDispatch\Event\DomainEventInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class CvekDomainEventsExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $frameworkBundle = $container->getParameter('kernel.bundles')['FrameworkBundle'] ?? null;
        if (isset($frameworkBundle)) {
            $config = [
                'messenger' => [
                    'transports' => [
                        'db' => [
                            'dsn' => 'doctrine://default',
                        ],
                    ],
                    'routing' => [
                        DomainEventInterface::class => 'db',
                    ],
                ],
            ];

            $container->prependExtensionConfig('framework', $config);
        }
    }
}
