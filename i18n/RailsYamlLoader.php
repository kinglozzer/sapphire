<?php

namespace SilverStripe\i18n;

use Symfony\Component\Yaml\Parser;
use Zend\I18n\Exception;
use Zend\I18n\Translator\Loader\AbstractFileLoader;
use Zend\I18n\Translator\TextDomain;

class RailsYamlLoader extends AbstractFileLoader
{
    /**
     * @param string $locale
     * @param string $filename
     * @return TextDomain
     * @throws Exception\InvalidArgumentException
     */
    public function load($locale, $filename)
    {
        $resolvedFile = $this->resolveFile($filename);
        if (!$resolvedFile) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Could not find or open file %s for reading',
                $filename
            ));
        }

        $parser = new Parser();
        $content = $parser->parse(file_get_contents($filename));
        if ($locale != 'auto' && $content && !array_key_exists($locale, $content)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Locale "%s" not found in file %s',
                $locale,
                $filename
             ));
        }

        $flattened = array();
        if($content && $content[$locale]) {
            $iterator = new RailsYamlIterator(new \RecursiveArrayIterator($content[$locale]));
            foreach($iterator as $k => $v) {
                $flattened[implode('.', $iterator->getKeyStack())] = $v;
            }
        }

        $textDomain = new TextDomain($flattened);
        return $textDomain;
    }
}

class RailsYamlIterator extends \RecursiveIteratorIterator
{
    protected $keyStack = array();

    public function callGetChildren() 
    {
      $this->keyStack[] = parent::key();
      return parent::callGetChildren();
    }

    public function endChildren() 
    {
      array_pop($this->keyStack);
      parent::endChildren();
    }

    public function key() 
    {
      return json_encode($this->getKeyStack());
    }

    public function getKeyStack() 
    {
      return array_merge($this->keyStack, array(parent::key()));
    }
}
