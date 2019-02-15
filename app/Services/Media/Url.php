<?php

namespace Coyote\Services\Media;

use Intervention\Image\ImageManager;

class Url
{
    /**
     * @var ImageManager
     */
    protected $imageManager;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var null|bool
     */
    protected $secure;

    /**
     * @param ImageManager $imageManager
     * @param File $file
     */
    public function __construct(ImageManager $imageManager, File $file)
    {
        $this->imageManager = $imageManager;
        $this->file = $file;
    }

    /**
     * @param bool|null $flag
     * @return $this
     */
    public function secure($flag)
    {
        $this->secure = $flag;

        return $this;
    }

    /**
     * Make thumbnail and return full url.
     *
     * @param string $template
     * @return string|null
     */
    public function thumbnail($template)
    {
        if (!$this->file->getFilename()) {
            return null;
        }

        $thumbnailPath = $this->thumbnailPath($template);

        if (!file_exists($this->file->path($thumbnailPath))) {
            $class = config("imagecache.templates.$template");
            $filter = new $class;

            $image = $this->imageManager->make($this->file->path());
            $image->filter($filter)->save($this->file->path($thumbnailPath));
        }

        return cdn($this->publicPath() . '/' . $thumbnailPath);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->makeUrl();
    }

    /**
     * @return string
     */
    protected function publicPath()
    {
        return implode('/', array_diff(explode('/', $this->file->root()), explode('/', public_path())));
    }

    /**
     * @return string
     */
    private function makeUrl()
    {
        if (!$this->file->getFilename()) {
            return ''; // because __toString() requires string value
        }

        if ($this->file->getDownloadUrl() && !$this->file->isImage()) {
            return $this->file->getDownloadUrl();
        }

        return cdn($this->publicPath() . '/' . $this->file->relative(), $this->secure);
    }

    /**
     * @param string $template
     * @return string
     */
    protected function thumbnailPath($template)
    {
        $pathinfo = pathinfo($this->file->relative());
        return $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '-' . $template . '.' . $pathinfo['extension'];
    }
}
