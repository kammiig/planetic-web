<?php

namespace Tests\Unit;

use App\Support\RegistrantValidator;
use PHPUnit\Framework\TestCase;

class RegistrantValidatorTest extends TestCase
{
    private function completeContact(): array
    {
        return [
            'first_name' => 'Kamran',
            'last_name' => 'Malik',
            'email' => 'k@example.com',
            'phone' => '+447732466055',
            'address_line_1' => '63 Shepherd Street',
            'city' => 'Bury',
            'state' => 'Lancashire',
            'postcode' => 'BL9 0RT',
            'country' => 'GB',
        ];
    }

    public function test_complete_contact_is_valid(): void
    {
        $this->assertSame([], RegistrantValidator::missing($this->completeContact()));
        $this->assertSame([], RegistrantValidator::formatIssues($this->completeContact(), 'co.uk'));
        $this->assertTrue(RegistrantValidator::isValid($this->completeContact()));
    }

    public function test_missing_and_placeholder_required_fields_are_flagged(): void
    {
        $contact = $this->completeContact();
        $contact['phone'] = '';                  // empty
        $contact['postcode'] = '00000';          // placeholder
        $contact['address_line_1'] = 'N/A';      // placeholder

        $missing = RegistrantValidator::missing($contact);

        $this->assertContains('Missing or placeholder phone number', $missing);
        $this->assertContains('Missing or placeholder postcode', $missing);
        $this->assertContains('Missing or placeholder address', $missing);
        $this->assertFalse(RegistrantValidator::isValid($contact));
    }

    public function test_format_issues_are_detected(): void
    {
        $contact = $this->completeContact();
        $contact['email'] = 'not-an-email';
        $contact['country'] = 'United Kingdom'; // not a 2-letter code
        $contact['phone'] = '123';              // too short

        $issues = RegistrantValidator::formatIssues($contact, 'com');

        $this->assertContains('Email address is not valid', $issues);
        $this->assertContains('Country must be a 2-letter ISO code (e.g. GB)', $issues);
        $this->assertContains('Phone number looks invalid (too short)', $issues);
    }

    public function test_uk_postcode_format_is_validated_for_co_uk(): void
    {
        $contact = $this->completeContact();
        $contact['postcode'] = 'XXXX'; // invalid UK postcode

        $issues = RegistrantValidator::formatIssues($contact, 'co.uk');

        $this->assertContains('UK postcode format looks invalid (expected e.g. "BL9 0RT")', $issues);
    }
}
