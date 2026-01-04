<?php
require_once 'bootstrap.php';

try {
    $app = new \Espo\Core\Application();
    $container = $app->getContainer();
    
    /** @var \Espo\Core\Utils\Config $config */
    $config = $container->get('config');
    
    $apiKey = 'AIzaSyDD6XRNePz8jeSxnjq7BvllWJx5S2J1XxY';
    
    $config->set('geminiApiKey', $apiKey);
    $config->save();
    
    echo "Successfully configured Gemini API Key.\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
