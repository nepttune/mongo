<?php

namespace Nepttune\Mongo\Diagnostics;

use Nepttune\Mongo\MongoClientException;
use Nette;
use Nette\PhpGenerator as Code;
use Tracy\Debugger;
use Tracy\IBarPanel;

class Panel implements IBarPanel
{
	use Nette\SmartObject;

	/**
	 * @var float
	 */
	private $totalTime = 0;

	/**
	 * @var array
	 */
	private $queries = [];

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * @var bool
	 */
	public $renderPanel = TRUE;

	/**
	 * @var string
	 */
	public $name;

    /** @noinspection MoreThanThreeArgumentsInspection */
    public function begin(string $db, string $coll, string $name, string $params)
	{
		$this->queries[] = (object) [
		    'name' => $name,
            'db' => $db,
            'coll' => $coll,
            'params' => $params
        ];
	}

    public function end(int $time, string $result)
    {
        $entry = end($this->queries);

        if ($entry)
        {
            $this->totalTime += ($time / 1000);

            $entry->time = $time;
            $entry->result = $result;
        }
    }

	/**
	 * @param \Exception|\Throwable $e
	 */
	public function error($e)
	{
		$this->errors[] = $e;
		if ($query = end($this->queries)) {
			$query->errors[] = $e;
		}
	}

	/**
	 * Renders HTML code for custom tab.
	 * @return string
	 */
	public function getTab()
	{
		return
			'<style>
				#nette-debug div.nepttune-MongoClientPanel table td,
				#tracy-debug div.nepttune-MongoClientPanel table td { text-align: right }
				#nette-debug div.nepttune-MongoClientPanel table td.nepttune-MongoClientPanel-cmd,
				#tracy-debug div.nepttune-MongoClientPanel table td.nepttune-MongoClientPanel-cmd { background: white !important; text-align: left }
				#nette-debug .nepttune-Mongo-panel svg,
				#tracy-debug .nepttune-Mongo-panel svg { vertical-align: bottom; max-height: 1.55em; width: 1.50em; }
			</style>' .
			'<span title="Mongo Storage' . ($this->name ? ' - ' . $this->name : '') . '" class="nepttune-Mongo-panel">' .
			file_get_contents(__DIR__ . '/logo.svg') .
			'<span class="tracy-label">' .
				count($this->queries) . ' queries' .
				($this->errors ? ' / ' . count($this->errors) . ' errors' : '') .
				($this->queries ? ' / ' . sprintf('%0.1f', $this->totalTime) . ' ms' : '') .
			'</span></span>';
	}

	public function getPanel() : string
	{
		if (!$this->renderPanel) {
			return '';
		}

		$s = '';
		$h = 'htmlSpecialChars';
		foreach ($this->queries as $query) {
			$s .= '<tr><td>' . sprintf('%0.3f', $query->time);
            $s .= '</td><td>' . $h($query->db);
            $s .= '</td><td>' . $h($query->coll);
			$s .= '</td><td class="nepttune-MongoClientPanel-cmd">' . $h($query->name);
			$s .= '</td><td>' . $h($query->params);
            $s .= '</td><td>' . $h($query->result);
			$s .= '</td></tr>';
		}

		return empty($this->queries) ? '' :
			'<h1>Queries: ' . count($this->queries) . ($this->totalTime ? ', time: ' . sprintf('%0.3f', $this->totalTime * 1000) . ' ms' : '') . '</h1>
			<div class="nette-inner tracy-inner nepttune-MongoClientPanel">
			<table>
				<tr><th>Time&nbsp;µs</th><th>DB</th><th>Collection</th><th>Command</th><th>Parameters</th><th>Result</th></tr>' . $s . '
			</table>
			</div>';
	}

	public static function renderException($e)
	{
		if ($e instanceof MongoClientException) {
			$panel = NULL;
			if ($e->request) {
				$panel .= '<h3>Mongo Request</h3>' .
					'<pre class="nette-dump"><span class="php-string">' .
					nl2br(htmlSpecialChars(implode(' ', $e->request))) .
					'</span></pre>';
			}
			if ($e->response) {
				$response = Code\Helpers::dump($e->response);
				$panel .= '<h3>Mongo Response (' . strlen($e->response) . ')</h3>' .
					'<pre class="nette-dump"><span class="php-string">' .
					htmlSpecialChars($response) .
					'</span></pre>';
			}

			if ($panel !== NULL) {
				$panel = [
					'tab' => 'Mongo Response',
					'panel' => $panel
				];
			}

			return $panel;
		}
	}

	public static function register()
	{
		self::getDebuggerBlueScreen()->addPanel([$panel = new static(), 'renderException']);
		self::getDebuggerBar()->addPanel($panel);
		return $panel;
	}

	private static function getDebuggerBar()
	{
		return Debugger::getBar();
	}

	private static function getDebuggerBlueScreen()
	{
		return Debugger::getBlueScreen();
	}
}
