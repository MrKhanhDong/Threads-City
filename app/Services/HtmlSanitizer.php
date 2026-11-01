<?php

namespace App\Services;

use DOMDocument;
use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    public function __construct(private MediaAltResolver $mediaAltResolver) {}

    public function clean(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Attr.DefaultImageAlt', '');
        $config->set('CSS.AllowTricky', true);
        $config->set('HTML.Allowed', 'p[style],div[style],span[style],h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],strong,b,em,i,u,s,strike,br,hr,blockquote[style],ul[style],ol[start|style],li[style],a[href|title|target|rel],img[src|alt|width|height|style],table[style],thead,tbody,tfoot,tr,td[colspan|rowspan|style],th[colspan|rowspan|style],caption,pre,code,sup,sub,figure,figcaption');
        $config->set('CSS.AllowedProperties', ['color', 'background-color', 'font-family', 'font-size', 'font-weight', 'font-style', 'text-decoration', 'text-align', 'margin-left', 'padding-left', 'width', 'height', 'border', 'border-collapse', 'vertical-align', 'float', 'display', 'margin', 'margin-right', 'margin-top', 'margin-bottom', 'max-width']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.Nofollow', true);
        $config->set('HTML.TargetBlank', true);
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));
        $config->set('HTML.DefinitionID', 'cms-richtext');
        $config->set('HTML.DefinitionRev', 2);
        if ($definition = $config->maybeGetRawHTMLDefinition()) {
            $definition->addElement('figure', 'Block', 'Flow', 'Common');
            $definition->addElement('figcaption', 'Block', 'Flow', 'Common');
        }
        $config->getCSSDefinition()->info['display'] = new \HTMLPurifier_AttrDef_Enum(['block', 'inline', 'inline-block']);

        $purifier = new HTMLPurifier($config);
        if (! str_contains(strtolower($html), '<img')) {
            return $purifier->purify($html);
        }
        $document = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
        $this->mediaAltResolver->apply($document);
        $output = '';
        foreach ($document->getElementsByTagName('body')->item(0)->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $purifier->purify($output);
    }

    /** @return array{html:string,headings:array<int,array{id:string,title:string,level:int}>} */
    public function withHeadings(string $html): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="article-content">'.$this->clean($html).'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $headings = [];
        $index = 0;
        foreach ((new \DOMXPath($document))->query('//h2 | //h3 | //h4') as $heading) {
            $id = 'section-'.(++$index);
            $heading->setAttribute('id', $id);
            $headings[] = ['id' => $id, 'title' => $heading->textContent, 'level' => (int) substr($heading->nodeName, 1)];
        }
        $root = $document->getElementById('article-content');
        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return ['html' => $output, 'headings' => $headings];
    }

    /** @return array<int,int> */
    public function mediaIds(string $html): array
    {
        preg_match_all('~<img[^>]+src=["\x27](?:https?://[^/"\x27]+)?/media/(\d+)~i', $html, $matches);

        return array_values(array_unique(array_map('intval', $matches[1])));
    }
}
