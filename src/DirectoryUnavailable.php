<?php
namespace NickGranados\RdsAuthBridge;

/** Thrown when the external user directory (RDS) cannot be reached or queried. */
final class DirectoryUnavailable extends \RuntimeException {}
