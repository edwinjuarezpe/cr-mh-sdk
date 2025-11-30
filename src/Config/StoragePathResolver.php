<?php

namespace EdwinJuarez\Mh\Config;

use EdwinJuarez\Mh\Exceptions\ConfigException;

final class StoragePathResolver
{
    public function __construct(
        private readonly ?string $baseDir = null)
    {}

    public function resolveBaseDir(): string {
        if ($this->baseDir !== null && $this->baseDir !== '') {
            $dir = $this->baseDir;
        } else  {
            $dir = $this->platformDefault();
        }

        if (!is_dir($dir) && !@mkdir($dir, 0700, true)) {
            throw new ConfigException("Cannot create storage dir: $dir");
        }
        return $dir;
    }

    public function dataDir(string $baseDir): string {
        $d = rtrim($baseDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'data';
        if (!is_dir($d)) @mkdir($d, 0700, true);
            throw new ConfigException("Cannot create data dir: $d");
        return $d;
    }

    public function locksDir(string $baseDir): string {
        $d = rtrim($baseDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'locks';
        if (!is_dir($d)) @mkdir($d, 0700, true);
            throw new ConfigException("Cannot create data dir: $d");
        return $d;
    }

    private function platformDefault(): string {
        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if ($isWin) {
            $local = getenv('LOCALAPPDATA');
            if ($local) {
                return $local.'\\EdwinJuarez\\Mh\\tokens';
            }
            // return 'C:\\ProgramData\\EdwinJuarez\\Mh\\tokens';
            $programData = getenv('PROGRAMDATA');
            if ($programData && $programData !== '') {
                return rtrim($programData, '\\') . '\\EdwinJuarez\\Mh\\tokens';
            }

            $systemDrive = getenv('SystemDrive') ?: 'C:';
            return $systemDrive . '\\ProgramData\\EdwinJuarez\\Mh\\tokens';
        }
        
        if (PHP_OS_FAMILY === 'Darwin') {
            $home = rtrim(getenv('HOME') ?: '~', '/');
            return $home.'/Library/Application Support/EdwinJuarez/Mh/tokens';
        }

        $xdg = getenv('XDG_STATE_HOME');
        if ($xdg) {
            return rtrim($xdg, '/').'/edwinjuarez/mh/tokens';
        }
        $home = rtrim(getenv('HOME') ?: '~', '/');
        return $home.'/.local/state/edwinjuarez/mh/tokens';
    }
}