<?php

namespace EvanPiAlert\Util;

use Exception;
use PharData;

/**
 * Автоматическое обновление кода в продуктивной системе на основе исходников GitHub
 */
class SelfUpdateCode {

    protected array $log = array();

    protected string $pathToRootFolder;
    protected string $tempFolder;
    protected string $projectFolderName;
    protected string $archiveName;

    public function __construct() {
        $this->pathToRootFolder = __DIR__.'/../../';
        $this->tempFolder = $this->pathToRootFolder.'temp/';
        $this->projectFolderName = 'pialert-main';
        $this->archiveName = $this->projectFolderName.'.tar.gz';
    }

    /**
     * Выполнить обновления кода системы на основе исходников из GirHub
     * @return string|true Либо текст ошибки, либо true
     */
    public function execute() : string|bool {
        $downloadLink = GITHUB_PROJECT_LINK."/archive/main.tar.gz";

        try {
            $this->prepareSteps();
            $this->downloadNewCodeVersion($downloadLink);
            $this->replaceOldCode();
            $this->afterSteps();
        } catch (Exception) {
            return implode(PHP_EOL, $this->log);
        }
        return true;
    }

    public function checkPrepareSteps() : bool {
        try {
            $this->prepareSteps();
        } catch (Exception) {
            return false;
        }
        return true;
    }

    /**
     * @throws Exception
     */
    protected function prepareSteps(): void {
        if ( !is_dir($this->tempFolder) ) {
            $this->createFolder($this->tempFolder);
        }
        if ( is_file($this->tempFolder.$this->archiveName) ) {
            $this->removeFile($this->tempFolder . $this->archiveName);
        }
        if ( count($this->log) > 0 ) {
            throw new Exception();
        }
    }

    /**
     * @throws Exception
     */
    protected function downloadNewCodeVersion(string $downloadLink) : void {
        $data = file_get_contents($downloadLink);
        if ( $data === false) {
            $this->log[] = 'Fail to download new code from '.$downloadLink;
            throw new Exception();
        }
        $result = file_put_contents($this->tempFolder.$this->archiveName, $data);
        if ( $result === false ) {
            $this->log[] = 'Fail to save new code to '.$this->tempFolder.$this->archiveName;
            throw new Exception();
        }
    }

    /**
     * @throws Exception
     */
    protected function replaceOldCode() : void {
        try {
            $pharData = new PharData($this->tempFolder.$this->archiveName);
            $pharData->extractTo($this->tempFolder, $this->projectFolderName.'/', true);
        } catch (Exception $e) {
            $this->log[] = 'Fail to extract new code files from archive. Error: '.$e->getMessage();
            throw new Exception();
        }
        $this->moveDir($this->tempFolder.$this->projectFolderName.'/', $this->pathToRootFolder);
        if ( count($this->log) > 0 ) {
            throw new Exception();
        }
    }

    /**
     * @throws Exception
     */
    protected function afterSteps(): void {
        $this->removeFile($this->tempFolder.$this->archiveName);
        $this->removeFolder($this->tempFolder);
        if ( count($this->log) > 0 ) {
            throw new Exception();
        }
    }

    protected function moveDir(string $from, string $to): void {
        $files = scandir($from);
        if ( !is_dir($to) ) {
            $this->createFolder($to);
        }
        foreach ($files as $file) {
            if ( $file == '.' or $file == '..' ) {
                continue;
            }
            if ( is_dir($from.$file) ) {
                $this->moveDir($from.$file.'/', $to.$file.'/');
            } else {
                $this->moveFile($from.$file, $to.$file);
            }
        }
        $this->removeFolder($from);
    }

    protected function moveFile(string $from, string $to): void {
        if (!rename($from, $to)) {
            $this->log[] .= 'Failed to update file '.$to;
        }
    }

    protected function removeFile(string $file) : void {
        if (@!unlink($file)) {
            $this->log[] .= 'Failed to delete file '.$file;
        }
    }

    protected function createFolder(string $folder): void {
        if (!mkdir($folder)) {
            $this->log[] .= 'Failed to create folder '.$folder;
        }
    }

    protected function removeFolder(string $folder): void {
        if (@!rmdir($folder)) {
            $this->log[] .= 'Failed to delete folder '.$folder;
        }
    }
}