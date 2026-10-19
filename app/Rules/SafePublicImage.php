<?php

namespace App\Rules;

use Closure;
use DOMDocument;
use Illuminate\Contracts\Validation\ValidationRule;

class SafePublicImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof \Illuminate\Http\UploadedFile || ! $value->isValid()) {
            $fail('Tệp ảnh không hợp lệ.');

            return;
        }
        if ($value->getClientOriginalExtension() !== 'svg' && $value->getMimeType() !== 'image/svg+xml') {
            if (@getimagesize($value->getRealPath()) === false) {
                $fail('Tệp phải là ảnh hợp lệ.');
            }

            return;
        }
        $xml = file_get_contents($value->getRealPath());
        if (preg_match('/<!DOCTYPE|<!ENTITY|<\?xml-stylesheet/i', $xml)) {
            $fail('SVG không được chứa tài nguyên hoặc thực thể bên ngoài.');

            return;
        }
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $loaded = $document->loadXML($xml, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $allowed = ['svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'title', 'desc', 'defs', 'linearGradient', 'radialGradient', 'stop', 'clipPath', 'mask', 'use'];
        if (! $loaded || $document->documentElement?->localName !== 'svg') {
            $fail('SVG không hợp lệ.');

            return;
        }
        foreach ($document->getElementsByTagName('*') as $node) {
            if (! in_array($node->localName, $allowed, true)) {
                $fail('SVG chứa thành phần không được phép.');

                return;
            }
            foreach ($node->attributes as $attributeNode) {
                $name = strtolower($attributeNode->localName);
                $content = $attributeNode->namespaceURI === 'http://www.w3.org/2000/xmlns/' ? '' : $attributeNode->value;
                if (str_starts_with($name, 'on') || $name === 'style' || $name === 'base'
                    || ($name === 'href' && ! str_starts_with($content, '#'))
                    || preg_match('/(?:javascript:|data:|https?:|\/\/|url\(\s*[\'"]?(?!#))/i', $name === 'xmlns' ? '' : $content)) {
                    $fail('SVG không được chứa script, sự kiện hoặc liên kết bên ngoài.');

                    return;
                }
            }
        }
    }
}
