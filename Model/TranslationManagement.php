<?php
declare(strict_types=1);

namespace Blackbird\TranslationDictionariesGenerator\Model;


use Blackbird\TranslationDictionariesGenerator\Api\TranslationManagementInterface;
use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\WriteInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\App\Emulation;

/**
 * Class TranslationManagement
 * @package Blackbird\TranslationDictionariesGenerator\Model
 */
class TranslationManagement implements TranslationManagementInterface
{
    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * @var \Magento\Framework\App\AreaList
     */
    protected $areaList;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvHandler;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory
     */
    protected $configCollectionFactory;

    /**
     * @var \Blackbird\TranslationDictionariesGenerator\Model\Config
     */
    protected $config;

    /**
     * TranslationManagement constructor.
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Framework\App\AreaList $areaList
     * @param \Magento\Framework\File\Csv $csvHandler
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $configCollectionFactory
     * @param \Blackbird\TranslationDictionariesGenerator\Model\Config $config
     */
    public function __construct(
        Emulation $appEmulation,
        AreaList $areaList,
        Csv $csvHandler,
        DirectoryList $directoryList,
        Filesystem $fileSystem,
        ConfigCollectionFactory $configCollectionFactory,
        Config $config
    ) {
        $this->appEmulation = $appEmulation;
        $this->areaList = $areaList;
        $this->csvHandler = $csvHandler;
        $this->directoryList = $directoryList;
        $this->fileSystem = $fileSystem;
        $this->configCollectionFactory = $configCollectionFactory;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     * TODO : Get the possibility to choose the destination for the generated file
     */
    public function export(string $originFilepath, string $locale, ?string $outputFilepath = null)
    {
        $storeId = $this->getFirstStoreIdByLocale($locale);

        if ($storeId !== null) {
            $this->appEmulation->startEnvironmentEmulation($storeId);
            $area = $this->areaList->getArea(Area::AREA_FRONTEND);
            $area->load(Area::PART_TRANSLATE);

            $csvData = $this->csvHandler->getData($originFilepath);

            $stream = $this->getStream($locale, $outputFilepath);

            $stream->writeCsv(self::EXPORT_CSV_HEADER);

            $existing = [];
            $notExisting = [];
            $excludedModules = $this->config->getExcludedModules();

            // Separate existing and not existing translations
            foreach ($csvData as $row => $data) {
                if (isset($data[3]) && \in_array($data[3], $excludedModules)) {
                    continue;
                }

                $translation = __($data[0])->render();

                if ($data[0] !== $translation) {
                    $existing[$data[0]] = [
                        'translation'  => $translation,
                        'module' => $data[3] ?? ''
                    ];
                } else {
                    $notExisting[$data[0]] = [
                        'translation'  => '',
                        'module' => $data[3] ?? ''
                    ];
                }
            }

            //add to CSV existing translation
            foreach ($existing as $default => $data) {
                $this->addTranslationCSVLine($stream, (string) $default, $data, $data);
            }

            //add to CSV not existing translation
            foreach ($notExisting as $default => $data) {
                $this->addTranslationCSVLine($stream, (string) $default, $data, $data);
            }

            $this->appEmulation->stopEnvironmentEmulation();
        } else {
            throw new NoSuchEntityException(new Phrase('Locale code %1 match any store', [$locale]));
        }
    }

    /**
     * @param $locale
     * @param string|null $outputFilepath
     * @return \Magento\Framework\Filesystem\File\WriteInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getStream($locale, ?string $outputFilepath = null): WriteInterface
    {
        $directory = $this->fileSystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $varDir = $this->fileSystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $varPath = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $tmpDir = $varDir->getAbsolutePath($varPath . '/' . self::EXPORT_VAR_DIR);

        if (!$directory->create($tmpDir)) {
            throw new \RuntimeException(new Phrase('Failed to create %1 directory', [self::EXPORT_VAR_DIR]));
        }

        $exportPath = $tmpDir . '/export_translation-' . $locale . '.csv';

        return $directory->openFile($exportPath, 'w+');
    }

    /**
     * Get one store id for a locale
     *
     * @param string $locale
     * @return string|null
     */
    protected function getFirstStoreIdByLocale(string $locale): ?string
    {
        return $this->configCollectionFactory->create()
            ->addFieldToSelect('scope_id')
            ->addFieldToFilter('path', Custom::XML_PATH_GENERAL_LOCALE_CODE)
            ->addFieldToFilter('value', $locale)
            ->getFirstItem()
            ->getData('scope_id');
    }

    /**
     * Add a new translation line to stream CSV
     *
     * @param Filesystem\File\WriteInterface $stream
     * @param string $key
     * @param array $current
     * @param array $new
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function addTranslationCSVLine(WriteInterface $stream, string $key, array $current, array $new): void
    {
        $text = [];
        $text[] = $key; //key
        $text[] = $current['translation']; //Current translation
        $text[] = $new['translation']; //New translation
        $text[] = $new['module']; //New translation
        $stream->writeCsv($text);
    }
}
