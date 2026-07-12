<?php

namespace libraries;

class FileEdit
{
    protected $imgArr = [];
    protected $uploadErrors = [];
    protected $directory;
    protected $uniqueFile = true;

    public function addFile($directory = '')
    {
        $this->imgArr = [];
        $this->uploadErrors = [];

        $directory = trim($directory, ' /');
        $directory .= '/';

        $this->setDirectory($directory);

        foreach ($_FILES as $key => $file) {
            if (is_array($file['name'])) {
                foreach ($file['name'] as $i => $value) {
                    $name = (string)($file['name'][$i] ?? '');
                    $error = (int)($file['error'][$i] ?? UPLOAD_ERR_NO_FILE);

                    if ($name === '' && $error === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    if ($error !== UPLOAD_ERR_OK) {
                        $this->uploadErrors[$key][$i] = [
                            'name' => $name,
                            'error' => $error,
                            'size' => (int)($file['size'][$i] ?? 0),
                        ];
                        continue;
                    }

                    $file_arr = [
                        'name' => $name,
                        'type' => $file['type'][$i] ?? '',
                        'tmp_name' => $file['tmp_name'][$i] ?? '',
                        'error' => $error,
                        'size' => (int)($file['size'][$i] ?? 0),
                    ];

                    $res_name = $this->createFile($file_arr);

                    if ($res_name) {
                        $this->imgArr[$key][] = $directory . $res_name;
                    }
                }
            } else {
                $name = (string)($file['name'] ?? '');
                $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

                if ($name === '' && $error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($error !== UPLOAD_ERR_OK) {
                    $this->uploadErrors[$key] = [
                        'name' => $name,
                        'error' => $error,
                        'size' => (int)($file['size'] ?? 0),
                    ];
                    continue;
                }

                $res_name = $this->createFile($file);

                if ($res_name) {
                    $this->imgArr[$key] = $directory . $res_name;
                }
            }
        }

        return $this->getFiles();
    }

    public function getErrors()
    {
        return $this->uploadErrors;
    }

    protected function createFile($file)
    {
        $fileNameArr = explode('.', $file['name']);
        $ext = $fileNameArr[count($fileNameArr) - 1];
        unset($fileNameArr[count($fileNameArr) - 1]);

        $fileName = implode('.', $fileNameArr);
        $fileName = (new TextModify())->translit($fileName);
        $fileName = $this->checkFile($fileName, $ext);

        $fileFullName = $this->directory . $fileName;

        if ($this->uploadFile($file['tmp_name'], $fileFullName)) {
            return $fileName;
        }

        return false;
    }

    protected function uploadFile($tmpName, $dest)
    {
        if (!$tmpName || !is_uploaded_file($tmpName)) {
            return false;
        }

        if (move_uploaded_file($tmpName, $dest)) {
            return true;
        }

        return false;
    }

    protected function checkFile($fileName, $ext, $fileLastName = '')
    {
        if (!file_exists($this->directory . $fileName . $fileLastName . '.' . $ext) || !$this->uniqueFile) {
            return $fileName . $fileLastName . '.' . $ext;
        }

        return $this->checkFile($fileName, $ext, '_' . hash('crc32', time() . mt_rand(1, 1000)));
    }

    public function setUniqueFile($value)
    {
        $this->uniqueFile = $value ? true : false;
    }

    public function setDirectory($directory)
    {
        $this->directory = $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . $directory;

        if (!file_exists($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    public function getFiles()
    {
        return $this->imgArr;
    }
}