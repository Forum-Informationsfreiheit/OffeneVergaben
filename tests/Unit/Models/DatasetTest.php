<?php

namespace Tests\Unit\Models;

use App\Dataset;
use Carbon\Carbon;
use Tests\TestCase;

class DatasetTest extends TestCase
{
    /**
     * @dataProvider formattedTextProvider
     */
    public function testFormattedTextsConvertLineBreaks($attribute, $accessor)
    {
        $dataset = (new Dataset())->forceFill([$attribute => "Zeile 1\nZeile 2"]);

        $this->assertSame('Zeile 1<br />Zeile 2', $dataset->{$accessor});
        $this->assertNull((new Dataset())->{$accessor});
    }

    public function formattedTextProvider()
    {
        return [
            'title'                 => ['title', 'title_formatted'],
            'description'           => ['description', 'description_formatted'],
            'info_modifications'    => ['info_modifications', 'info_modifications_formatted'],
            'justification'         => ['justification', 'justification_formatted'],
            'procedure_description' => ['procedure_description', 'procedure_description_formatted'],
        ];
    }

    /**
     * Values are stored in cents and shown in euros.
     */
    public function testFormattedValues()
    {
        $dataset = (new Dataset())->forceFill([
            'val_total'        => 123456789,
            'val_total_before' => 100,
            'val_total_after'  => 5,
        ]);

        $this->assertSame('1.234.567,89', $dataset->val_total_formatted);
        $this->assertSame('1,00', $dataset->val_total_before_formatted);
        $this->assertSame('0,05', $dataset->val_total_after_formatted);

        $empty = new Dataset();
        $this->assertNull($empty->val_total_formatted);
        $this->assertNull($empty->val_total_before_formatted);
        $this->assertNull($empty->val_total_after_formatted);
    }

    /**
     * Some labels depend on the dataset type, see resources/lang/de/dataset.php.
     */
    public function testLabelDependsOnType()
    {
        $this->assertSame('Auftragswert', (new Dataset())->forceFill(['type_code' => 'KD_8_2_Z1'])->label('val_total'));
        $this->assertSame('Preisgeld', (new Dataset())->forceFill(['type_code' => 'KD_8_2_Z2'])->label('val_total'));
        // types without a label of their own get the translation key
        $this->assertSame('dataset.val_total', Dataset::labelStatic('val_total', 'KD_8_1_Z1'));
    }

    /**
     * item_lastmod is a timestamp with microseconds.
     */
    public function testItemLastmodKeepsMicroseconds()
    {
        $dataset = new Dataset();

        $dataset->item_lastmod = Carbon::createFromFormat('Y-m-d H:i:s.u', '2026-03-01 10:15:00.123456');

        $this->assertSame('2026-03-01 10:15:00.123456', $dataset->getAttributes()['item_lastmod']);
        $this->assertSame('2026-03-01 10:15:00.123456', $dataset->item_lastmod->format('Y-m-d H:i:s.u'));

        $dataset->item_lastmod = null;

        $this->assertNull($dataset->item_lastmod);
    }
}
