<?php

namespace Tests;

use Crwlr\SchemaOrg\SchemaOrg;
use Spatie\SchemaOrg\Article;
use Spatie\SchemaOrg\FAQPage;
use Spatie\SchemaOrg\JobPosting;
use Spatie\SchemaOrg\Organization;

it('extracts JSON-LD schema.org data from an HTML document', function () {
    $html = <<<HTML
        <!DOCTYPE html>
        <html lang="de">
        <head><title>Foo Bar</title></head>
        <body>
        <script type="application/ld+json">{"@context":"https:\/\/schema.org","@type":"JobPosting","title":"Senior Full Stack PHP Developer (w\/m\/d)","employmentType":["FULL_TIME"],"datePosted":"2022-07-25","description":"foo bar baz","hiringOrganization":{"@type":"Organization","name":"Foo Ltd.","logo":"https:\/\/www.example.com\/logo.png"},"jobLocation":{"@type":"Place","address":{"@type":"PostalAddress","addressLocality":"Linz","addressRegion":"Upper Austria","addressCountry":"Austria"}},"identifier":{"@type":"PropertyValue","name":"foo","value":123456},"directApply":true} </script>
        <h1>Baz</h1> <p>Other content</p>
        </body>
        </html>
        HTML;

    $schemaOrgObjects = SchemaOrg::fromHtml($html);

    expect($schemaOrgObjects)->toHaveCount(1);

    expect($schemaOrgObjects[0])->toBeInstanceOf(JobPosting::class);
});

it('extracts multiple JSON-LD schema.org items from one document in head and body', function () {
    $html = <<<HTML
        <!DOCTYPE html>
        <html lang="de-AT">
        <head>
        <title>Foo Bar</title>
        <script type="application/ld+json">
        {
            "mainEntity": [{
                "name": "Some Question?",
                "acceptedAnswer": {
                    "text": "bli bla blub!",
                    "@type": "Answer"
                },
                "@type": "Question"
            }, {
                "name": "Another question?",
                "acceptedAnswer": {
                    "text": "bla blu blo!",
                    "@type": "Answer"
                },
                "@type": "Question"
            }],
            "@type": "FAQPage",
            "@context": "http://schema.org"
        }
        </script>
        <meta property="og:title" content="Some Article" />
        <meta property="og:type" content="website" />
        <script type="application/ld+json">
        { "@context": "http://schema.org",
        "@type": "Organization",
        "name": "Example Company",
        "url": "https://www.example.com",
        "logo": "https://www.example.com/logo.png", "sameAs": [ "https://some.social-media.app/example-company" ] }
        </script>
        </head>
        <body>
        <h1>Some Article</h1>
        <h2>This is some article about something.</h2>
        <script type="application/ld+json">
        {
            "@context": "https:\/\/schema.org",
            "@type": "Article",
            "name": "Some Article",
            "url": "https:\/\/de.example.org\/articles\/some",
            "sameAs": "http:\/\/www.example.org\/articles\/A123456789",
            "mainEntity": "http:\/\/www.example.org\/articles\/A123456789",
            "author": {
                "@type": "Organization",
                "name": "Some Organization"
            },
            "publisher": {
                "@type": "Organization",
                "name": "Some Organization, Inc.",
                "logo": {
                    "@type": "ImageObject",
                    "url": "https:\/\/www.example.org\/images\/organization-logo.png"
                }
            },
            "datePublished": "2023-09-07T21:57:44Z",
            "image": "https:\/\/images.example.org\/2023\/A123456789.jpg",
            "headline": "This is some article about something."
        }
        </script>
        </body>
        </html>
        HTML;

    $schemaOrgObjects = SchemaOrg::fromHtml($html);

    expect($schemaOrgObjects)->toHaveCount(3);

    expect($schemaOrgObjects[0])->toBeInstanceOf(FAQPage::class);

    expect($schemaOrgObjects[1])->toBeInstanceOf(Organization::class);

    expect($schemaOrgObjects[2])->toBeInstanceOf(Article::class);
});

it('also converts child schema.org objects to spatie class instances', function () {
    $html = <<<HTML
        <!DOCTYPE html>
        <html lang="de-AT">
        <head>
        <title>Some Article</title>
        <meta property="og:title" content="Some Article" />
        <meta property="og:type" content="website" />
        </head>
        <body>
        <h1>Some Article</h1>
        <h2>This is some article about something.</h2>
        <script type="application/ld+json">
        {
            "@context": "https:\/\/schema.org",
            "@type": "Article",
            "name": "Some Article",
            "url": "https:\/\/de.example.org\/articles\/some",
            "sameAs": "http:\/\/www.example.org\/articles\/A123456789",
            "mainEntity": "http:\/\/www.example.org\/articles\/A123456789",
            "author": {
                "@type": "Organization",
                "name": "Some Organization"
            },
            "publisher": {
                "@type": "Organization",
                "name": "Some Organization, Inc.",
                "logo": {
                    "@type": "ImageObject",
                    "url": "https:\/\/www.example.org\/images\/organization-logo.png"
                }
            },
            "datePublished": "2023-09-07T21:57:44Z",
            "image": "https:\/\/images.example.org\/2023\/A123456789.jpg",
            "headline": "This is some article about something."
        }
        </script>
        </body>
        </html>
        HTML;

    $schemaOrgObjects = SchemaOrg::fromHtml($html);

    expect($schemaOrgObjects[0])->toBeInstanceOf(Article::class);

    expect($schemaOrgObjects[0]->getProperty('publisher'))->toBeInstanceOf(Organization::class);

    expect($schemaOrgObjects[0]->getProperty('publisher')->getProperty('name'))->toBe('Some Organization, Inc.');
});
