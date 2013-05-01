<?php

namespace Zroger\Feather\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Yaml\Yaml;

/**
 * FeatherYamlFileLoader loads a feather.yml configuration file.  The parsed
 * YAML data is loaded into the container under the feather namespace, hence
 * only values known to FeatherExtension are allowed.
 */
class FeatherYamlFileLoader extends FileLoader
{
    /**
     * Loads a Yaml file.
     *
     * @param mixed  $file The resource
     * @param string $type The resource type
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);

        $content = $this->loadFile($path);

        $this->container->addResource(new FileResource($path));

        // empty file
        if (null === $content) {
            return;
        }

        // extensions
        $this->container->loadFromExtension('feather', $content);
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * Loads a YAML file.
     *
     * @param string $file
     *
     * @return array The file content
     */
    protected function loadFile($file)
    {
        return $this->validate(Yaml::parse($file), $file);
    }

    /**
     * Validates a YAML file.
     *
     * @param mixed  $content
     * @param string $file
     *
     * @return array
     *
     * @throws InvalidArgumentException When service file is not valid
     */
    private function validate($content, $file)
    {
        if (null === $content) {
            return $content;
        }

        if (!is_array($content)) {
            throw new InvalidArgumentException(sprintf('The service file "%s" is not valid.', $file));
        }

        return $content;
    }
}
