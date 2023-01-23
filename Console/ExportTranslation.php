<?php
declare(strict_types=1);

namespace Blackbird\TranslationDictionariesGenerator\Console;

use Blackbird\TranslationDictionariesGenerator\Api\TranslationManagementInterface;
use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Phrase;
use Magento\Setup\Module\I18n\ServiceLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigCollectionFactory;

/**
 * Class ExportTranslation
 * @package Blackbird\TranslationDictionariesGenerator\Console
 */
class ExportTranslation extends Command
{
    protected const INPUT_FILE = 'input';
    protected const INPUT_FILE_SHORTCUT = 'i';
    protected const LOCALE_CODE = 'locale_code';
    protected const LOCALE_CODE_SHORTCUT = 'l';
    protected const LOCALE_CODE_DEFAULT = 'all';
    protected const OUTPUT_FILE = 'output';
    protected const OUTPUT_FILE_SHORTCUT = 'o';

    /**
     * @var \Blackbird\TranslationDictionariesGenerator\Api\TranslationManagementInterface
     */
    protected $translationManagement;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory
     */
    protected $configCollectionFactory;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $driverFile;

    /**
     * @var string
     */
    protected $phrasePath = '';

    /**
     * ExportTranslation constructor.
     * @param \Blackbird\TranslationDictionariesGenerator\Api\TranslationManagementInterface $translationManagement
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $configCollectionFactory
     * @param \Magento\Framework\Filesystem\Driver\File $driverFile
     * @param string|null $name
     */
    public function __construct(
        TranslationManagementInterface $translationManagement,
        State $state,
        DirectoryList $directoryList,
        ConfigCollectionFactory $configCollectionFactory,
        Filesystem\Driver\File $driverFile,
        string $name = null
    ) {
        $this->translationManagement = $translationManagement;
        $this->state = $state;
        $this->directoryList = $directoryList;
        $this->configCollectionFactory = $configCollectionFactory;
        $this->driverFile = $driverFile;
        parent::__construct($name);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $options = [
            new InputOption(
                self::LOCALE_CODE,
                self::LOCALE_CODE_SHORTCUT,
                InputOption::VALUE_REQUIRED,
                'Locale code to determine your export language (ex: fr_FR, en_US ...), if not fill it will export all configured languages',
                self::LOCALE_CODE_DEFAULT
            ),
            new InputOption(
                self::INPUT_FILE,
                self::INPUT_FILE_SHORTCUT,
                InputOption::VALUE_OPTIONAL,
                'Optional : full path of your input CSV file containing all Magento text. By default we use the Magento collect phrase command'
            ),
            new InputOption(
                self::OUTPUT_FILE,
                self::OUTPUT_FILE_SHORTCUT,
                InputOption::VALUE_OPTIONAL,
                'Optional : choose your output path file, by default it\'s {magento-dir}/var/transaltion/'
            ),
        ];

        $this->setName('blackbird:translation:export');
        $this->setDescription('Export translation by locale');
        $this->setDefinition($options);

        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $localeCode = $input->getOption(self::LOCALE_CODE);
        $destination = $input->getOption(self::OUTPUT_FILE);
        $output->writeln(new Phrase('Start export for %1 locale(s)', [$localeCode]));

        try {
            $this->state->setAreaCode(Area::AREA_FRONTEND);

            if (!($phrasesFile = $input->getOption(self::INPUT_FILE))) {
                $this->collectPhrases();
                $phrasesFile = $this->getPhrasesPath();
            }

            $locales = ($localeCode === self::LOCALE_CODE_DEFAULT) ? $this->getAllConfiguredLocales() : \explode(',', $localeCode);

            foreach ($locales as $locale) {
                $this->translationManagement->export($phrasesFile, $locale);
            }

            $this->deletePhrasesFile();

            $filepath = $destination ?: $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/' . TranslationManagementInterface::EXPORT_VAR_DIR . '';

            $output->writeln('Translation export finish ! Find it here : ' . $filepath);
        } catch (NoSuchEntityException | LocalizedException $e) {
            $output->writeln($e->getMessage());
        }
    }

    /**
     * Get all configured locales
     *
     * @return array
     */
    protected function getAllConfiguredLocales(): array
    {
        /** @var \Magento\Config\Model\ResourceModel\Config\Data\Collection $locales */
        $locales = $this->configCollectionFactory->create()
            ->addFieldToSelect('value')
            ->addFieldToFilter('path', Custom::XML_PATH_GENERAL_LOCALE_CODE);

        $locales->getSelect()
            ->group('value');

        return $locales->getColumnValues('value');
    }

    /**
     * Use Magento to create a file containing all strings
     */
    protected function collectPhrases(): void
    {
        $generator = ServiceLocator::getDictionaryGenerator();
        $generator->generate(
            '',
            $this->getPhrasesPath(),
            true
        );
    }

    /**
     * Get magento generated phrases file path in var/tmp dir
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getPhrasesPath(): string
    {
        if (!$this->phrasePath) {
            $this->phrasePath = $this->directoryList->getPath(DirectoryList::TMP) . '/phrases.csv';
        }

        return $this->phrasePath;
    }

    /**
     * Delete tmp magento generated phrases file
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function deletePhrasesFile(): void
    {
        if ($this->driverFile->isExists($this->getPhrasesPath())) {
            $this->driverFile->deleteFile($this->getPhrasesPath());
        }
    }
}
