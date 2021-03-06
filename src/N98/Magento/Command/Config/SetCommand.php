<?php

namespace N98\Magento\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetCommand extends AbstractConfigCommand
{
    protected function configure()
    {
        $this
            ->setName('config:set')
            ->setDescription('Set a core config item')
            ->addArgument('path', InputArgument::REQUIRED, 'The config path')
            ->addArgument('value', InputArgument::REQUIRED, 'The config value')
            ->addOption(
                'scope',
                null,
                InputOption::VALUE_OPTIONAL,
                'The config value\'s scope (default, websites, stores)',
                'default'
            )
            ->addOption('scope-id', null, InputOption::VALUE_OPTIONAL, 'The config value\'s scope ID', '0')
            ->addOption(
                'encrypt',
                null,
                InputOption::VALUE_NONE,
                'The config value should be encrypted using local.xml\'s crypt key'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Allow creation of non-standard scope-id\'s for websites and stores'
            )
        ;

        $help = <<<HELP
Set a store config value by path.
To set a value of a specify store view you must set the "scope" and "scope-id" option.

HELP;
        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $config = $this->_getConfigModel();
        if (!$config->getResourceModel()) {
            // without a resource model, a config option can't be saved.
            return;
        }

        $allowZeroScope = $input->getOption('force');

        $scope = $input->getOption('scope');
        $this->_validateScopeParam($scope);
        $scopeId = $this->_convertScopeIdParam($scope, $input->getOption('scope-id'), $allowZeroScope);

        $value = str_replace(array('\n', '\r'), array("\n", "\r"), $input->getArgument('value'));
        $value = $this->_formatValue($value, ($input->getOption('encrypt') ? 'encrypt' : false));

        $config->saveConfig(
            $input->getArgument('path'),
            $value,
            $scope,
            $scopeId
        );

        $output->writeln(
            '<comment>' . $input->getArgument('path') . "</comment> => <comment>" . $input->getArgument('value') .
            '</comment>'
        );
    }
}
