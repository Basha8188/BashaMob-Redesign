<?php

namespace App\Services;

use InvalidArgumentException;

class Ean13BarcodeService
{
    private const L = ['0001101', '0011001', '0010011', '0111101', '0100011', '0110001', '0101111', '0111011', '0110111', '0001011'];
    private const G = ['0100111', '0110011', '0011011', '0100001', '0011101', '0111001', '0000101', '0010001', '0001001', '0010111'];
    private const R = ['1110010', '1100110', '1101100', '1000010', '1011100', '1001110', '1010000', '1000100', '1001000', '1110100'];
    private const PARITY = ['LLLLLL', 'LLGLGG', 'LLGGLG', 'LLGGGL', 'LGLLGG', 'LGGLLG', 'LGGGLL', 'LGLGLG', 'LGLGGL', 'LGGLGL'];

    public function svg(string $barcode, string $label): string
    {
        if (! preg_match('/^\d{13}$/', $barcode)) {
            throw new InvalidArgumentException('EAN-13 must contain exactly 13 digits.');
        }

        $parity = self::PARITY[(int) $barcode[0]];
        $bits = '101';
        for ($i = 1; $i <= 6; $i++) {
            $bits .= $parity[$i - 1] === 'L' ? self::L[(int) $barcode[$i]] : self::G[(int) $barcode[$i]];
        }
        $bits .= '01010';
        for ($i = 7; $i <= 12; $i++) {
            $bits .= self::R[(int) $barcode[$i]];
        }
        $bits .= '101';

        $bars = '';
        foreach (str_split($bits) as $index => $bit) {
            if ($bit === '1') {
                $bars .= '<rect x="'.(20 + ($index * 3)).'" y="35" width="3" height="112"/>';
            }
        }
        $safeLabel = htmlspecialchars($label, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return '<svg xmlns="http://www.w3.org/2000/svg" width="325" height="205" viewBox="0 0 325 205" role="img" aria-label="Barcode '.$barcode.'"><rect width="325" height="205" rx="12" fill="white"/><g fill="#071a3d">'.$bars.'</g><text x="162.5" y="22" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" font-weight="700" fill="#071a3d">'.$safeLabel.'</text><text x="162.5" y="171" text-anchor="middle" font-family="monospace" font-size="18" letter-spacing="4" fill="#071a3d">'.$barcode.'</text><text x="162.5" y="194" text-anchor="middle" font-family="Arial,sans-serif" font-size="11" fill="#169ee8">BashaMob</text></svg>';
    }
}
