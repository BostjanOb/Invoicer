<?php

namespace App\View\Components;

use finfo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\ViewException;

class InlineImage extends Component
{
    public function __construct(public string $image) {}

    public function render(): View
    {
        if (! Str::of($this->image)->isUrl()) {
            try {
                $content = file_get_contents($this->image);
            } catch (\Exception $exception) {
                throw new ViewException('Image not found: '.$exception->getMessage());
            }
        } else {
            $response = Http::get($this->image);

            if (! $response->successful()) {
                throw new ViewException('Failed to fetch the image: '.$response->toException());
            }

            $content = $response->body();
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($content) ?: 'image/png';

        return view('components.inline-image')
            ->with('content', $content)
            ->with('mime', $mime);
    }
}
