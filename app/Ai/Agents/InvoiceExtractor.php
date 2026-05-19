<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[Model('gpt-5.4')]
#[Timeout(120)]
class InvoiceExtractor implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        You extract structured data from a single Slovenian invoice ("RAČUN") PDF.

        Rules:
        - The customer is the block under the "Naročnik:" heading. The company shown
          in the page header is the ISSUER — never return the issuer as the customer.
        - "ID za DDV" in the customer block is the customer's vat_number.
        - "Številka računa" looks like "001-2025". Return only the integer part as
          `number` (strip leading zeros and the "-year" suffix): "001-2025" -> 1.
        - "Datum računa" is issue_date. "Datum plačila" is payment_deadline. Return
          all dates as YYYY-MM-DD.
        - There is no paid date on the document; return null for paid_at.
        - `service_text` is the free-text paragraph shown to the left of the dates, under the Naročnik block, above the table.
          Return null if there is none.
        - Each row of the "Opis / Cena / Količina" table is one item. `title` is the
          bold line, `description` is the smaller line beneath it (null if absent).
        - Amounts use European formatting (e.g. "1.234,56 €"). Return plain decimal
          numbers using a dot as the decimal separator and no thousands separator or
          currency symbol (1234.56). `price` is the unit price, not the line total.
        PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer' => $schema->object(fn (JsonSchema $schema): array => [
                'name' => $schema->string()->required(),
                'address' => $schema->string()->required(),
                'city' => $schema->string()->required(),
                'postcode' => $schema->string()->required(),
                'country' => $schema->string()->required(),
                'vat_number' => $schema->string()->required(),
            ])->required(),
            'invoice' => $schema->object(fn (JsonSchema $schema): array => [
                'number' => $schema->integer()->required(),
                'issue_date' => $schema->string()->required(),
                'payment_deadline' => $schema->string()->required(),
                'paid_at' => $schema->string()->nullable()->required(),
                'service_text' => $schema->string()->nullable()->required(),
            ])->required(),
            'items' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'title' => $schema->string()->required(),
                    'description' => $schema->string()->nullable()->required(),
                    'price' => $schema->number()->required(),
                    'quantity' => $schema->number()->required(),
                ])
            )->required(),
        ];
    }
}
