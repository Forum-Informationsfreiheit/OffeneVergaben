<?php

namespace Tests\Unit\Models;

use App\Models\Contractor;
use App\Models\Offeror;
use App\Models\Post;
use App\Models\ScraperKerndaten;
use Tests\TestCase;

class OtherModelsTest extends TestCase
{
    public function testOfferorToStringJoinsTheValuesThatAreSet()
    {
        $offeror = (new Offeror())->forceFill([
            'national_id'      => '90001',
            'name'             => 'Stadt Wien',
            'domain'           => null,
            'phone'            => '+43 1 4000',
            'email'            => '',
            'contact'          => 'Maria Muster',
            'reference_number' => 'MA-42',
        ]);

        $this->assertSame('90001 | Stadt Wien | +43 1 4000 | Maria Muster | MA-42', $offeror->toString());
        $this->assertSame('90001<br>Stadt Wien<br>+43 1 4000<br>Maria Muster<br>MA-42', $offeror->toHtmlString());
    }

    public function testContractorToStringJoinsTheValuesThatAreSet()
    {
        $contractor = (new Contractor())->forceFill(['national_id' => null, 'name' => 'Muster Bau GmbH']);

        $this->assertSame('Muster Bau GmbH', $contractor->toString());
        $this->assertSame('Muster Bau GmbH', $contractor->toHtmlString());

        $contractor->national_id = 'FN123456a';

        $this->assertSame('FN123456a | Muster Bau GmbH', $contractor->toString());
        $this->assertSame('FN123456a<br>Muster Bau GmbH', $contractor->toHtmlString());
    }

    public function testPostImageAndThumb()
    {
        $post = (new Post())->forceFill(['image_filepath' => 'uploads/images/shares', 'image_filename' => 'foto.jpg']);

        $this->assertSame('uploads/images/shares/foto.jpg', $post->image);
        $this->assertSame('uploads/images/shares/'.config('lfm.thumb_folder_name').'/foto.jpg', $post->thumb);
        $this->assertNull((new Post())->image);
        $this->assertNull((new Post())->thumb);
    }

    /**
     * The raw XML of a kerndaten record, indented for display.
     */
    public function testScraperKerndatenXmlFormattedHtml()
    {
        $record = (new ScraperKerndaten())->forceFill(['xml' => '<KD_8_2_Z1><TITLE><P>Test</P></TITLE></KD_8_2_Z1>']);

        $this->assertSame(
            "<?xml version=\"1.0\"?>\n<KD_8_2_Z1>\n  <TITLE>\n    <P>Test</P>\n  </TITLE>\n</KD_8_2_Z1>\n",
            $record->xml_formatted_html
        );
    }
}
