<?php

/**
 * This file is part of CommuneChatbot.
 *
 * @link     https://github.com/thirdgerb/chatbot
 * @document https://github.com/thirdgerb/chatbot/blob/master/README.md
 * @contact  <thirdgerb@gmail.com>
 * @license  https://github.com/thirdgerb/chatbot/blob/master/LICENSE
 */

namespace Commune\Ghost;

use Commune\Ghost\Bootstrap;
use Commune\Framework\App\AbsAppKernel;
use Commune\Blueprint\Configs\GhostConfig;
use Commune\Blueprint\Exceptions\CommuneBootingException;
use Commune\Blueprint\Framework\ReqContainer;
use Commune\Blueprint\Kernel\Protocals\AppProtocal;
use Commune\Blueprint\Framework\ServiceRegistry;
use Commune\Blueprint\Framework\Session;
use Commune\Blueprint\Ghost;
use Commune\Blueprint\Ghost\Cloner;
use Commune\Blueprint\Kernel\Protocals\GhostResponse;
use Commune\Container\ContainerContract;
use Commune\Contracts\Log\ConsoleLogger;
use Commune\Contracts\Log\LogInfo;
use Commune\Protocals\Comprehension;
use Commune\Protocals\Intercom\InputMsg;
use Commune\Support\Protocal\ProtocalMatcher;


/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class IGhost extends AbsAppKernel implements Ghost
{

    protected $bootstrappers = [
        // 注册配置 Option 单例到进程中.
        Bootstrap\GhostLoadConfigOption::class,
        // 注册相关服务
        Bootstrap\GhostRegisterProviders::class,
        // 注册相关组件
        Bootstrap\GhostLoadComponent::class,
    ];

    /**
     * @var GhostConfig
     */
    protected $config;

    /**
     * @var ProtocalMatcher
     */
    protected $requestProtoMatcher;

    /**
     * @var ProtocalMatcher
     */
    protected $apiProtoMatcher;


    public function __construct(
        GhostConfig $config,
        ContainerContract $procC = null,
        ReqContainer $reqC = null,
        ServiceRegistry $registrar = null,
        ConsoleLogger $consoleLogger = null,
        LogInfo $logInfo = null
    )
    {
        $this->config = $config;
        parent::__construct($procC, $reqC, $registrar, $consoleLogger, $logInfo);
    }

    public function getName(): string
    {
        return $this->config->name;
    }

    public function getId(): string
    {
        return $this->config->id;
    }

    protected function basicBindings(): void
    {
        $this->instance(GhostConfig::class, $this->config);
        $this->instance(Ghost::class, $this);
    }

    public function getConfig(): GhostConfig
    {
        return $this->config;
    }

    /**
     * @param ReqContainer $container
     * @param InputMsg $input
     * @return Cloner
     */
    public function makeSession(ReqContainer $container, InputMsg $input): Session
    {
        if (!$this->activated) {
            throw new CommuneBootingException(
                'Ghost not activated'
            );
        }

        $cloner = new ICloner($this, $container, $input);

        $container->share(InputMsg::class, $input);
        $container->share(Comprehension::class, $input->comprehension);
        $container->share(Cloner::class, $cloner);

        return $cloner;
    }

    /*--------- protocals ---------*/

    protected function getProtocalOptions(): array
    {
        return $this->getConfig()->protocals;
    }

    protected function isValidFinaleResponse(AppProtocal $protocal): bool
    {
        return $protocal instanceof GhostResponse;
    }


}