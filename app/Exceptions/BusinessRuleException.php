<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Biznes qoidasi buzilganda (noto'g'ri status o'tishi, kuryer band va h.k.).
 * API'da 422 JSON, admin panelda flash xabar sifatida qaytadi (bootstrap/app.php).
 */
class BusinessRuleException extends RuntimeException {}
