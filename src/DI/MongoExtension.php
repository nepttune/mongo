<?php

namespace Nepttune\Mongo\DI;

class MongoExtension extends \Nette\DI\CompilerExtension
{
    /** @var array  */
    public $defaults = [
        'connection' => [],
    ];

    /** @var array  */
	public $clientDefaults = [
		'host' => 'mongodb://mongo/Test',
        'user' => null,
        'password' => null,
        'database' => null,
		'debugger' => '%debugMode%',
        'ssl' => false
	];

	/** @var array  */
	private $configuredClients = [];

	public function loadConfiguration() : void
	{
		$this->configuredClients = [];

		foreach ($this->getConfig($this->defaults)['connection'] as $name => $clientConfig) {
			$this->buildClient($name, $clientConfig);
		}

        if (empty($this->configuredClients))
        {
            $this->buildClient(null, $this->clientDefaults);
        }
	}

	protected function buildClient($name, $config) : \Nette\DI\ServiceDefinition
	{
		$builder = $this->getContainerBuilder();
		$config = \Nette\DI\Config\Helpers::merge($config, $this->clientDefaults);
		$config = array_intersect_key($config, $this->clientDefaults);
        $clientName = $this->prefix(($name ?: 'default') . '.client');
        $contextName = $this->prefix(($name ?: 'default') . '.context');

		$options = [
		    'ssl' => (bool) $config['ssl']
        ];
		if ($config['user'])
        {
            $options['user'] = $config['user'];
        }
        if ($config['password'])
        {
            $options['password'] = $config['password'];
        }

		$client = $builder->addDefinition($clientName)
			->setClass(\MongoDB\Driver\Manager::class, [
				'uri' => $config['host'],
                'options' => $options
			]);
		$context = $builder->addDefinition($contextName)
            ->setClass(\MongoDB\Database::class, [
                'manager' => '@\MongoDB\Driver\Manager',
                'databaseName' => $config['database']
            ]);

		$client->addTag('mongo.client');
        $context->addTag('mongo.context');

        if (array_key_exists('debugger', $config) && $config['debugger'])
		{
            $panelName = $this->prefix(($name ?: 'default') . '.panel');
            $watcherName = $this->prefix(($name ?: 'default') . '.watcher');

			$builder->addDefinition($panelName)
				->setClass(\Nepttune\Mongo\Diagnostics\Panel::class)
				->setFactory('Nepttune\Mongo\Diagnostics\Panel::register')
				->addSetup('$name', [$name ?: 'default']);

			$builder->addDefinition($watcherName)
                ->setClass(\Nepttune\Mongo\Diagnostics\Watcher::class)
                ->addSetup('setPanel', ['@' . $panelName])
                ->addTag('run');
		}

        $this->configuredClients[$name] = $config;

		return $context;
	}

	public static function register(\Nette\Configurator $config) : void
	{
		$config->onCompile[] = function ($config, \Nette\DI\Compiler $compiler) {
			$compiler->addExtension('Mongo', new MongoExtension());
		};
	}
}
