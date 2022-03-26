<?php

namespace Fastwf\Tests\Engine;

use Fastwf\Core\Configuration;
use Fastwf\Core\Engine\Engine;

class TestingEngine extends Engine
{

    private $testSettings;

    public function __construct($configurationPath = null, $settings = []) {
        parent::__construct($configurationPath);

        $this->config = new Configuration($configurationPath);
        $this->testSettings = $settings;
    }
    
    public function getSettings() {
        return $this->testSettings;
    }

}
