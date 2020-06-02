<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost\Providers;

use Commune\Blueprint\CommuneEnv;
use Commune\Blueprint\Ghost;
use Commune\Blueprint\Ghost\MindSelfRegister;
use Commune\Container\ContainerContract;
use Commune\Contracts\ServiceProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 *
 * @property-read string $id
 * @property-read string[] $prs4
 */
class Psr4SelfRegisterLoader extends ServiceProvider
{
    const IDENTITY = 'id';

    public static function stub(): array
    {
        return [
            'id' => static::class,
            'psr4' => [],
        ];
    }

    public function boot(ContainerContract $app): void
    {
    }

    public function register(ContainerContract $app): void
    {
        $mind = $app->get(Ghost\Mindset::class);
        $logger = $app->get(LoggerInterface::class);

        foreach ($this->prs4 as $namespace => $path) {
            static::loadSelfRegister(
                $mind,
                $namespace,
                $path,
                $logger
            );
        }
    }


    public static function loadSelfRegister(
        Ghost\Mindset $mind,
        string $namespace,
        string $directory,
        LoggerInterface $logger
    ) : void
    {
        $finder = new Finder();
        $finder->files()
            ->in($directory)
            ->name('/\.php$/');

        $i = 0;
        foreach ($finder as $fileInfo) {

            $path = $fileInfo->getPathname();
            $name = str_replace($directory, '', $path);
            $name = str_replace('.php', '', $name);
            $name = str_replace('/', '\\', $name);

            $clazz = trim($namespace, '\\')
                . '\\' .
                trim($name, '\\');

            if (!is_a($clazz, MindSelfRegister::class, TRUE)) {
                continue;
            }

            // 判断是不是可以实例化的.
            $r = new \ReflectionClass($clazz);
            if (!$r->isInstantiable()) {
                continue;
            }

            $logger->debug("register context $clazz");
            $method = [$clazz, MindSelfRegister::REGISTER_METHOD];
            call_user_func($method, $mind);
            $i ++;
        }

        if (empty($i)) {
            $logger->warning(
                'no self register class found,'
                . "namespace is $namespace,"
                . "directory is $directory"
            );
        }

    }

}