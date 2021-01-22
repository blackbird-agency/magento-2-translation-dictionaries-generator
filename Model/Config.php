<?php
declare(strict_types=1);

namespace Blackbird\TranslationDictionariesGenerator\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 * @package Blackbird\TranslationDictionariesGenerator\Model
 */
class Config
{
    public const CONFIG_PATH_BLACKBIRD_TRANSLATIONDIRECTORIESGENERATOR_EXCLUDED_MODULES = 'blackbird_translationdictionariesgenerator/general/excluded_modules';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Config constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get an array of excluded modules name for translation export
     *
     * @return array
     */
    public function getExcludedModules(): array
    {
        return \explode(',', $this->scopeConfig->getValue(self::CONFIG_PATH_BLACKBIRD_TRANSLATIONDIRECTORIESGENERATOR_EXCLUDED_MODULES) ?: '');
    }
}
