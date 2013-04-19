<?php

namespace Zroger\Feather\Phar;

use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use Herrera\Phar\Update\Update;
use KevinGH\Version\Version;

class UpdateManager extends Manager
{

    /**
     * Updates the running Phar if any is available.  Overridden to return the
     * Update instance used.
     *
     * @param string|Version $version  The current version.
     * @param boolean        $major    Lock to current major version?
     * @param boolean        $pre      Allow pre-releases?
     *
     * @return boolean|Update Update instance if an update was performed, FALSE if none available.
     */
    public function update($version, $major = false, $pre = false)
    {
        if (false === ($version instanceof Version)) {
            $version = Version::create($version);
        }

        if (null !== ($update = $this->getManifest()->findRecent(
            $version,
            $major,
            $pre
        ))){
            $update->getFile();
            $update->copyTo($this->getRunningFile());

            return $update;
        }

        return false;
    }
}

