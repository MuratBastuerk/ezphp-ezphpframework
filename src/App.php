<?php


namespace Mb7\EzPhp\EzPhpFramework;

use Mb7\EzPhp\EzPhpFramework\Exception\EzConfigurationException;
use Mb7\EzPhp\Mvc\MVCInterface;
use Mb7\EzPhp\Router\RouterInterface;
use Mb7\EzPhp\ServiceManager\DI\Factory\ServiceManagerFactory;
use Mb7\EzPhp\ServiceManager\DI\ServiceLocatorInterface;

/**
 * Class App
 * @package Mb7\EzPhp\EzPhpFramework
 */
class App
{
    /**
     *
     * Provides a set of predifined values for configuring the application
     *
     * @var array
     */
    private $configurationArray;
    /**
     *
     * Checks configurationArray against expected keys
     *
     * @var array
     */
    private $configurationKeys;

    /**
     *
     * Holds the registered classes by given configuration
     *
     * @var array
     */
    private $appRegistration = [];

    /**
     * @var ServiceLocatorInterface
     */
    private $serviceManager;

    public function __construct(array $configurationArray)
    {
        $this->configurationArray = $configurationArray;
        $this->configurationKeys = [
            "Factories" => ["ServiceManagerFactory"],
            "ApplicationServices" => ["MVCInterface"],
            "GlobalVariables",
        ];
        $this->configureApplicationFactories();
        $this->configureApplicationServices();
    }

    /**
     * Starts the app
     */
    public function run()
    {
        $mvc = $this->getMVC();
        $mvc->getRouter()->resolveRoute($mvc->getController());
    }

    /**
     *
     * Router is set during instantiation via configuration array
     *
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->getMVC()->getRouter();
    }

    /**
     * @return MVCInterface
     */
    public function getMVC(): MVCInterface
    {
        return $this->getServiceManager()->get("MVCInterface");
    }

    /**
     * @return ServiceLocatorInterface
     */
    public function getServiceManager(): ServiceLocatorInterface
    {
        return $this->serviceManager;
    }

    /**
     * @param ServiceLocatorInterface $serviceManager
     */
    public function setServiceManager(ServiceLocatorInterface $serviceManager): void
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     *
     * The configuration array can provide custom globalVariables, which are accessible application wide
     *
     * @return array|mixed
     */
    public function getGlobalVariables()
    {
        if ($this->checkValueInArray("GlobalVariables", $this->configurationArray)) {
            return $this->configurationArray["GlobalVariables"];
        }
        return ["no globalVariables specified"];
    }

    /**
     *
     * Helper function to check array value
     *
     * @param $value
     * @param array $haystack
     * @return bool
     */
    private function checkValueInArray($value, array $haystack): bool
    {
        return array_key_exists($value, $haystack) && !empty($haystack[$value]);
    }

    /**
     *
     * Iterates through configuration array
     *
     * @throws EzConfigurationException
     */
    private function configureApplicationFactories()
    {
        foreach ($this->configurationKeys["Factories"] as $configurationKey) {
            if ($this->checkValueInArray($configurationKey, $this->configurationArray["Factories"])) {
                $class = $this->configurationArray["Factories"][$configurationKey];
                if (class_exists($class)) {
                    $this->appRegistration["Factories"][$configurationKey] = new $class();
                    if ($configurationKey == "ServiceManagerFactory") {
                        $this->setServiceManager($this->appRegistration["Factories"][$configurationKey]->getServiceManager());
                    }
                } else {
                    throw new EzConfigurationException("Configuration can not find class: $class. Please specify a instantiable class.");
                }
            } else {
                throw new EzConfigurationException("Configuration is missing following key: $configurationKey");
            }
        }
    }

    /**
     *
     * Iterates through configuration array
     *
     * @throws EzConfigurationException
     */
    private function configureApplicationServices()
    {
        foreach ($this->configurationKeys["ApplicationServices"] as $configurationKey) {
            if ($this->checkValueInArray($configurationKey, $this->configurationArray["ApplicationServices"])) {
                $this->getServiceManager()->registerService($configurationKey, $this->configurationArray["ApplicationServices"][$configurationKey]);
            } else {
                throw new EzConfigurationException("Configuration is missing following key: $configurationKey");
            }
        }
    }
}