<?php
declare(strict_types=1);

namespace Blackbird\TranslationDictionariesGenerator\Api;

/**
 * Interface TranslationManagementInterface
 * @package Blackbird\TranslationDictionariesGenerator
 */
interface TranslationManagementInterface
{
    public const EXPORT_CSV_HEADER = [
        'Key',
        'Current Translation',
        'New Translation',
        'Module'
    ];

    public const EXPORT_VAR_DIR = 'translation_export';

    /**
     * Export a translation file by a store id for the language
     *
     * @param string $originFilepath
     * @param string $locale
     * @param string | null $outputFilepath
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function export(string $originFilepath, string $locale, ?string $outputFilepath = null);
}
