<?php

namespace Astro\Ephemeris;

class EphemerisConfig
{
    private string $ephemerisPath;
    private string $libraryPath;

    public function __construct(
        string $ephemerisPath = '/usr/local/swisseph/ephe',
        string $libraryPath = '/usr/lib/libswe.so'
    ) {
        $this->ephemerisPath = $ephemerisPath;
        $this->libraryPath = $libraryPath;
    }

    public function getEphemerisPath(): string
    {
        return $this->ephemerisPath;
    }

    public function getLibraryPath(): string
    {
        return $this->libraryPath;
    }

    public function setEphemerisPath(string $path): self
    {
        $this->ephemerisPath = $path;
        return $this;
    }

    public function setLibraryPath(string $path): self
    {
        $this->libraryPath = $path;
        return $this;
    }
}
