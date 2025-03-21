<?php

namespace Interfaces;

interface DatabaseInterface
{
    public function getConnection();
    public function prepare($sql);
    public function query($sql);
    public function execute($sql);
    public function lastInsertId();
    public function quote($string);
}