<?php

namespace Zroger\Feather\Config;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlConfigLoader extends FileLoader
{
    public function load($resource, $type = null)
    {
        $values = Yaml::parse($resource);

        // relative paths in yaml config are relative to the yaml file.
        if (isset($values['document_root'])) {
            $values['document_root'] = $this->resolveRelativePath($values['document_root'], dirname($resource));
        }

        if (isset($values['server_root'])) {
            $values['document_root'] = $this->resolveRelativePath($values['document_root'], dirname($resource));
        }

        return $values;
    }

    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    protected function resolveRelativePath($path, $basepath)
    {
        if (strpos($path, '/') !== 0) {
            return $basepath . '/' . $path;
        }
        return $path;
    }
}
