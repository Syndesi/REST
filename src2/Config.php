<?php
namespace Syndesi\REST;

$config = [
  'methods' => [
    'GET',
    'POST',
    'PUT',
    'PATCH',
    'DELETE',
    'COPY',
    'HEAD',
    'OPTIONS',
    'LINK',
    'UNLINK',
    'PURGE',
    'LOCK',
    'UNLOCK',
    'PROPFIND',
    'VIEW',
    'TRACE'
  ],
  'datetime' => [
    'format'   => \DateTime::ATOM
  ]
];

?>