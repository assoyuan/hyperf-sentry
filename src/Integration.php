<?php
namespace Minbaby\HyperfSentry;

use Psr\Container\ContainerInterface;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\FlushableClientInterface;
use Sentry\Integration\IntegrationInterface;
use Sentry\SentrySdk;
use Sentry\State\Scope;

use function Sentry\addBreadcrumb;
use function Sentry\configureScope;

class Integration implements IntegrationInterface
{

    /**
     * @var null|string
     */
    private static $transaction;

    /**
     * {@inheritdoc}
     */
    public function setupOnce(): void
    {
        Scope::addGlobalEventProcessor(function (Event $event): Event {
            $self = SentryContext::getHub()->getIntegration(self::class);

            if (!$self instanceof self) {
                return $event;
            }

            $event->setTransaction($self->getTransaction());

            return $event;
        });
    }

    /**
     * Adds a breadcrumb if the integration is enabled for Laravel.
     *
     * @param Breadcrumb $breadcrumb
     */
    public static function addBreadcrumb(Breadcrumb $breadcrumb): void
    {
        $self = SentryContext::getHub()->getIntegration(self::class);

        if (!$self instanceof self) {
            return;
        }

        SentryContext::getHub()->addBreadcrumb($breadcrumb);
    }

    /**
     * Configures the scope if the integration is enabled for Laravel.
     *
     * @param callable $callback
     */
    public static function configureScope(callable $callback): void
    {
        $self = SentryContext::getHub()->getIntegration(self::class);

        if (!$self instanceof self) {
            return;
        }

        SentryContext::getHub()->configureScope($callback);
    }

    /**
     * @return null|string
     */
    public static function getTransaction()
    {
        return self::$transaction;
    }

    /**
     * @param null|string $transaction
     */
    public static function setTransaction($transaction): void
    {
        self::$transaction = $transaction;
    }

    /**
     * Block until all async events are processed for the HTTP transport.
     *
     * @internal This is not part of the public API and is here temporarily until
     *  the underlying issue can be resolved, this method will be removed.
     */
    public static function flushEvents(): void
    {
        $client = SentryContext::getHub()->getClient();

        if ($client instanceof FlushableClientInterface) {
            $client->flush();
        }
    }
}