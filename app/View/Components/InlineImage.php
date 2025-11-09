<?php

namespace App\View\Components;

use finfo;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InlineImage extends Component
{
    public function __construct(public string $image) {}

    public function render(): View
    {
        if (! \Illuminate\Support\Str::of($this->image)->isUrl()) {
            try {
                $content = file_get_contents($this->image);
            } catch (\Exception $exception) {
                throw new \Illuminate\View\ViewException('Image not found: '.$exception->getMessage());
            }
        } else {
            $response = \Illuminate\Support\Facades\Http::get($this->image);

            if (! $response->successful()) {
                throw new \Illuminate\View\ViewException('Failed to fetch the image: '.$response->toException());
            }

            $content = $response->body();
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($content) ?: 'image/png';

        return view('components.inline-image')
            ->with('content', $content)
            ->with('mime', $mime);
    }
}
