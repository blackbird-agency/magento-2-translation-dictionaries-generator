<?php
declare(strict_types=1);

namespace Blackbird\TranslationDictionariesGenerator\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Module\FullModuleList;

/**
 * Class ModuleList
 * @package Blackbird\TranslationDictionariesGenerator\Model\Config\Source
 */
class ModuleList implements OptionSourceInterface
{
    /**
     * @var \Magento\Framework\Module\FullModuleList
     */
    protected $fullModuleList;

    /**
     * ModuleList constructor.
     * @param \Magento\Framework\Module\FullModuleList $fullModuleList
     */
    public function __construct(
        FullModuleList $fullModuleList
    ) {
        $this->fullModuleList = $fullModuleList;
    }

    /**
     * {@inheritDoc}
     */
    public function toOptionArray(): array
    {
        $options = [];

        $names = $this->fullModuleList->getNames();
        \sort($names);

        foreach ($names as $moduleName) {
            $options[] = [
                'value' => $moduleName,
                'label' => $moduleName
            ];
        }

        return $options;
    }
}
