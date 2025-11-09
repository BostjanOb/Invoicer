<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans text-[12px]">

<div class="grid grid-cols-2 mx-8 items-center">
    <div class="text-center pr-12">
        <x-inline-image :image="\Storage::path($user->company_logo)"/>
    </div>
    <div class="flex flex-col gap-1">
        <p class="flex gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-[#F0A941]">
                <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" />
            </svg>
            {{ $user->company_name }}
        </p>
        <div class="flex gap-1 items-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-[#F0A941]">
                <path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 0 0-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.145.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" />
            </svg>
            <p>
                {{ $user->company_address }},
                {{ $user->company_postcode }} {{ $user->company_city }},
                {{ $user->company_country }}
            </p>
        </div>
        <div class="flex gap-2 items-center">
            <div class="flex gap-1 items-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-[#F0A941]">
                    <path fill-rule="evenodd" d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 0 1-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 0 0 6.697 6.697c.103.038.25.009.352-.126l.97-1.293a1.875 1.875 0 0 1 1.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z" clip-rule="evenodd" />
                </svg>
                {{ $user->phone }}
            </div>

            <div class="flex gap-1 items-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-[#F0A941]">
                    <path d="M1.5 8.67v8.58a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V8.67l-8.928 5.493a3 3 0 0 1-3.144 0L1.5 8.67Z" />
                    <path d="M22.5 6.908V6.75a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3v.158l9.714 5.978a1.5 1.5 0 0 0 1.572 0L22.5 6.908Z" />
                </svg>
                {{ $user->email }}
            </div>
        </div>
        <div class="flex gap-1 items-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 text-[#F0A941]">
                <path fill-rule="evenodd" d="M4.5 3.75a3 3 0 0 0-3 3v10.5a3 3 0 0 0 3 3h15a3 3 0 0 0 3-3V6.75a3 3 0 0 0-3-3h-15Zm4.125 3a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Zm-3.873 8.703a4.126 4.126 0 0 1 7.746 0 .75.75 0 0 1-.351.92 7.47 7.47 0 0 1-3.522.877 7.47 7.47 0 0 1-3.522-.877.75.75 0 0 1-.351-.92ZM15 8.25a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 0-1.5H15ZM14.25 12a.75.75 0 0 1 .75-.75h3.75a.75.75 0 0 1 0 1.5H15a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 0-1.5H15Z" clip-rule="evenodd" />
            </svg>
            <p>Davčna številka: {{ $user->company_vat_number }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 items-end my-8">
    <div class="bg-[#F0A941] h-1.5"></div>
    <div class="bg-[#F0A941] h-0.5"></div>
</div>

<div class="grid grid-cols-2 mx-8">
    <div class="pr-12">
        <p class="uppercase text-neutral-500 font-bold">Naročnik:</p>
        <p>{{ $invoice->customer->name }}</p>
        <p>{{ $invoice->customer->address }}</p>
        <p>{{ $invoice->customer->postcode }} {{ $invoice->customer->city }}</p>
        <p>ID za DDV: {{ $invoice->customer->vat_number }}</p>
    </div>
    <div class="flex flex-col gap-1">
        <p class="text-3xl font-bold">RAČUN</p>
        <p>Številka računa: {{ $invoice->fullNumber() }}</p>
    </div>
</div>

<div class="grid grid-cols-2 mx-8 mt-8 items-end">
    <div>
        {{ $invoice->service_text }}
    </div>
    <div class="grid grid-cols-2">
        <div class="flex flex-col gap-1">
            <p class="text-neutral-500">Datum računa:</p>
            <b>{{ $invoice->issue_date->isoFormat('D. MMMM YYYY') }}</b>
        </div>
        <div class="flex flex-col gap-1">
            <p class="text-neutral-500">Datum plačila:</p>
            <b>{{ $invoice->payment_deadline->isoFormat('D. MMMM YYYY') }}</b>
        </div>
    </div>
</div>

<div class="grid grid-cols-[2rem_3fr_1fr_1fr_1fr_2rem] my-6 *:py-2.5">
    <div class="bg-[#F0A941]"></div>
    <div class="bg-[#F0A941] font-bold uppercase">Opis</div>
    <div class="bg-[#D9D9D9] font-bold uppercase text-right">Cena</div>
    <div class="bg-[#D9D9D9] font-bold uppercase text-right pr-3">Količina</div>
    <div class="bg-[#D9D9D9] font-bold uppercase text-right">Znesek</div>
    <div class="bg-[#D9D9D9]"></div>

    @foreach($invoice->items as $item)
        <div class="border-b-[#F0A941] border-b-2"></div>
        <div class="font-bold border-b-[#F0A941] border-b-2">
            <p>{{ $item->title }}</p>
            @if($item->description)
                <p class="text-[10px]">{{ $item->description }}</p>
            @endif
        </div>
        <div class="text-right border-b-[#D9D9D9] border-b-2">
            {{ Number::currency($item->price) }}
        </div>
        <div class="text-right border-b-[#D9D9D9] border-b-2 pr-3">
            {{ Number::format($item->quantity) }}
        </div>
        <div class="text-right border-b-[#D9D9D9] border-b-2">
            {{ Number::currency($item->price * $item->quantity) }}
        </div>
        <div class="border-b-[#D9D9D9] border-b-2"></div>
    @endforeach

    @for($i = count($invoice->items); $i <= 5; $i++)
        <div class="border-b-[#F0A941] border-b-2"></div>
        <div class="font-bold border-b-[#F0A941] border-b-2">&nbsp;</div>
        <div class="text-right border-b-[#D9D9D9] border-b-2">
        </div>
        <div class="text-right border-b-[#D9D9D9] border-b-2 pr-3">
        </div>
        <div class="text-right border-b-[#D9D9D9] border-b-2">
        </div>
        <div class="border-b-[#D9D9D9] border-b-2"></div>
    @endfor

    @php
        $row = max(count($invoice->items),5)+3;
    @endphp

    <div style="grid-column: 2; grid-row: {{ $row }}/{{ $row+2 }}" class="text-[9px]">
        DDV ni obračunan v skladu s 94 členom ZDDV-1.<br>
        Nisem davčni zavezanec.
    </div>

    <div class="text-right pr-3 font-bold" style="grid-column: 3/5; grid-row: {{ $row }}">Skupaj brez DDV</div>
    <div class="text-right pr-3 font-bold" style="grid-column: 3/5; grid-row: {{ $row+1 }}">DDV</div>
    <div class="text-right pr-3 font-bold bg-[#D9D9D9]" style="grid-column: 3/5; grid-row: {{ $row+2 }}">SKUPAJ</div>

    <div class="text-right font-bold" style="grid-column: 5/6; grid-row: {{ $row }}">
        {{ Number::currency($invoice->total()) }}
    </div>
    <div class="text-right font-bold" style="grid-column: 5/6; grid-row: {{ $row+1 }}">
        {{ Number::currency(0) }}
    </div>
    <div class="text-right font-bold bg-[#F0A941]" style="grid-column: 5/6; grid-row: {{ $row+2 }}">
        {{ Number::currency($invoice->total()) }}
    </div>
    <div class="bg-[#F0A941]" style="grid-column: 6/7; grid-row: {{ $row+2 }}"></div>
</div>


<div class="grid grid-cols-2 mx-8 mt-8">
    <div class="text-[11px]">
        <p><b>NAVEDENI ZNESEK NAKAŽITE NA POSLOVNI RAČUN:</b></p>
        <p><b>IBAN</b>: {{ $user->bank_iban }}</p>
        <p><b>BIC</b>: {{ $user->bank_bic }}</p>
        <p><b>Banka</b>: {{ $user->bank_name }}</p>
        <br>
        <p>Za sklic uporabite številko računa!</p>
    </div>
    <div class="flex flex-col text-center">
        <p class="text-lg font-bold">{{ $user->name }}</p>
        <div>
            <x-inline-image class="h-14 mx-auto " :image="\Storage::path($user->signature)"/>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 items-end my-8">
    <div class="border-t border-[#F0A941] pt-2 px-8">
        Hvala za sodelovanje!
    </div>
    <div class="border-t border-[#D9D9D9] pt-2 text-neutral-500">
        Podjetje ne posluje z žigom
    </div>
</div>

</body>
</html>
