<?php

namespace Maximaster\Coupanda;

use Bitrix\Main\IO\FileNotOpenedException;
use Bitrix\Main\IO\IoException;
use Bitrix\Main\Localization\Loc;

class FileInstaller
{
    protected $documentRoot;
    protected $bitrixAdminLinksDir;
    protected $moduleId;
    protected $moduleDir;
    protected $moduleComponentsDir;
    protected $moduleAdminScriptsDir;

    public function __construct($moduleDir, $documentRoot)
    {
        Loc::loadMessages(__FILE__);
        $this->documentRoot = realpath($documentRoot);

        if (!$this->documentRoot) {
            throw new \InvalidArgumentException(Loc::getMessage('MAXIMASTER.COUPANDA:FILE_INSTALLER_DOCROOT_INVALID'));
        }

        $moduleDir = realpath($moduleDir);
        if (!$moduleDir) {
            throw new \InvalidArgumentException('MAXIMASTER.COUPANDA:FILE_INSTALLER_MODULE_DIR_INVALID');
        }

        $this->moduleDir = $moduleDir;
        $this->moduleId = basename($moduleDir);

        $this->bitrixAdminLinksDir      = $this->documentRoot . $this->createPath('bitrix', 'admin');
        $this->moduleComponentsDir      = $this->moduleDir . $this->createPath('install', 'components');
        $this->moduleAdminScriptsDir    = $this->moduleDir . $this->createPath('admin');
        $this->bitrixComponentsDir      = $this->documentRoot . BX_ROOT . $this->createPath('components');
    }

    /**
     * Функция из кусочков создает полноценный путь с учетом системного разделителя папок
     * @return string
     */
    protected function createPath()
    {
        $s = DIRECTORY_SEPARATOR;
        $parts = func_get_args();
        return str_replace([$s.$s, '//',], $s, $s . implode($s, $parts));
    }

    protected function checkWriteability()
    {
        if (!is_writable($this->bitrixAdminLinksDir)){
            throw new IoException('MAXIMASTER.COUPANDA:FILE_INSTALLER_NOT_ENOUGH_RIGHTS', [
                'DIRECTORY' => $this->bitrixAdminLinksDir
            ]);
        }

        if (!is_writable($this->bitrixComponentsDir)) {
            throw new IoException('MAXIMASTER.COUPANDA:FILE_INSTALLER_NOT_ENOUGH_RIGHTS', [
                'DIRECTORY' => $this->bitrixComponentsDir
            ]);
        }
    }

    public function install()
    {
        $this->checkWriteability();
        $this->installAdminScripts();
        $this->installComponents();
        return true;
    }

    public function uninstall()
    {
        $this->checkWriteability();
        $this->uninstallAdminScripts();
        $this->uninstallComponents();
        return true;
    }

    protected function installAdminScripts()
    {
        // Относительный путь от сайта до директории с файлами
        $relAdminScriptDir = str_replace($this->documentRoot, '', $this->moduleAdminScriptsDir);

        try {
            $iterator = new \DirectoryIterator($this->moduleAdminScriptsDir);
        } catch (\UnexpectedValueException $e) {
            throw new FileNotOpenedException($this->moduleAdminScriptsDir, $e);
        }

        foreach ($iterator as $dirObject) {

            if (!$dirObject->isFile()) {
                continue;
            }

            $file = $dirObject->getFileInfo();
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $baseName = $file->getBasename();

            // Пропускаем файл меню, на него не нужно создавать скрипт
            if ($baseName === 'menu.php') {
                continue;
            }

            // Как назовем файл
            $linkFileName = $this->moduleId . '_' . $file->getFilename();

            // Куда его положим
            $linkFileDir = $this->documentRoot . $this->createPath('bitrix', 'admin');

            // Ссылка на файл в модуле
            $link = $this->createPath($relAdminScriptDir, $file->getFilename());

            // Содержимое файла-ссылки
            $linkFileContents = '<?require $_SERVER[\'DOCUMENT_ROOT\']."' . $link . '";?>';

            if (!file_put_contents($this->createPath($linkFileDir, $linkFileName), $linkFileContents)) {
                throw new IoException(Loc::getMessage('MAXIMASTER.COUPANDA:FILE_INSTALLER_CANT_CREATE_FILE', [
                    'FILE' => $linkFileName
                ]));
            }
        }
    }

    protected function uninstallAdminScripts()
    {
        $pattern = $this->createPath($this->bitrixAdminLinksDir, $this->moduleId.'_*');
        foreach (glob($pattern) as $file) {
            if (!unlink($file)) {
                throw new IoException(Loc::getMessage('MAXIMASTER.COUPANDA:FILE_INSTALLER_CANT_DELETE_FILE', [
                    'FILE' => $file
                ]));
            }
        }
    }

    protected function installComponents()
    {
        if (!CopyDirFiles($this->moduleComponentsDir, $this->bitrixComponentsDir, true, true)) {
            throw new IoException(Loc::getMessage('MAXIMASTER.COUPANDA:FILE_INSTALLER_CANT_INSTALL_COMPONENTS', [
                'DIRECTORY' => $this->bitrixComponentsDir
            ]));
        }

        return true;
    }

    protected function uninstallComponents()
    {
        try {
            $iterator = new \DirectoryIterator($this->moduleComponentsDir);
        } catch (\UnexpectedValueException $e) {
            throw new FileNotOpenedException($this->moduleComponentsDir, $e);
        }

        foreach ($iterator as $dirObject) {
            if (!$dirObject->isDir() || $dirObject->isDot()) {
                continue;
            }

            $componentNamespace = $dirObject->getFilename();

            foreach (new \DirectoryIterator($dirObject->getRealPath()) as $component) {
                if (!$component->isDir() || $component->isDot()) {
                    continue;
                }

                $relativeComponentPath = $this->createPath('bitrix', 'components', $componentNamespace, $component->getFilename());
                if (!DeleteDirFilesEx($relativeComponentPath)) {
                    throw new IoException(Loc::getMessage('MAXIMASTER.COUPANDA:FILE_INSTALLER_CANT_DELETE_COMPONENT', [
                        'COMPONENT' => $relativeComponentPath
                    ]));
                }
            }
        }
    }
}
