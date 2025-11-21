<?php

namespace Tests;

use Crwlr\SchemaOrg\SchemaOrg;
use Psr\Log\LoggerInterface;
use Spatie\SchemaOrg\AggregateOffer;
use Spatie\SchemaOrg\Article;
use Spatie\SchemaOrg\Dataset;
use Spatie\SchemaOrg\FAQPage;
use Spatie\SchemaOrg\JobPosting;
use Spatie\SchemaOrg\NewsArticle;
use Spatie\SchemaOrg\Offer;
use Spatie\SchemaOrg\Organization;
use Spatie\SchemaOrg\Product;

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

    expect($schemaOrgObjects)->toHaveCount(1)->
        and($schemaOrgObjects[0])->toBeInstanceOf(JobPosting::class);
});

it('gets schema.org objects from an array inside a JSON-LD script block', function () {
    $html = <<<HTML
        <!DOCTYPE html>
        <html lang="de">
        <head>
        <title>Foo Bar</title>
        <script type="application/ld+json">[{"@type":"NewsArticle","@context":"https:\/\/schema.org","articleBody":"Lorem\u202fipsum","articleSection":["politics"],"author":[{"@type":"Person","name":"Christian Olear","url":"https:\/\/www.example.com\/profiles\/christian-olear"}],"dateModified":"2025-01-10T05:00:42.623Z","description":"dolor sit amet","headline":"asdf","alternativeHeadline":"jkl\u00f6"},{"@type":"NewsArticle","@context":"https:\/\/schema.org","articleBody":"Foo bar","articleSection":["science"],"author":[{"@type":"Person","name":"Christian Olear","url":"https:\/\/www.example.com\/profiles\/christian-olear"}],"dateModified":"2025-01-10T05:03:12.623Z","description":"pew pew pew","headline":"pew pew","alternativeHeadline":"pew"}]</script>
        </head>
        <body>
        <h1>Foo</h1> <p>content</p>
        </body>
        </html>
        HTML;

    $schemaOrgObjects = SchemaOrg::fromHtml($html);

    expect($schemaOrgObjects)->toHaveCount(2)->
        and($schemaOrgObjects[0])->toBeInstanceOf(NewsArticle::class)->
        and($schemaOrgObjects[0]->getProperty('articleBody'))->toBe('Lorem ipsum')->
        and($schemaOrgObjects[1])->toBeInstanceOf(NewsArticle::class)->
        and($schemaOrgObjects[1]->getProperty('articleBody'))->toBe('Foo bar');
    ;
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

    expect($schemaOrgObjects)->toHaveCount(3)->
        and($schemaOrgObjects[0])->toBeInstanceOf(FAQPage::class)->
        and($schemaOrgObjects[1])->toBeInstanceOf(Organization::class)->
        and($schemaOrgObjects[2])->toBeInstanceOf(Article::class);
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

    expect($schemaOrgObjects[0])->toBeInstanceOf(Article::class)->
        and($schemaOrgObjects[0]->getProperty('publisher'))->toBeInstanceOf(Organization::class)->
        and($schemaOrgObjects[0]->getProperty('publisher')->getProperty('name'))->toBe('Some Organization, Inc.');
});

test('there is no error if a json-ld script block contains an invalid JSON string', function () {
    $html = <<<HTML
        <!DOCTYPE html>
        <html lang="de-AT">
        <head>
        <title>Some Article</title>
        </head>
        <body>
        <h1>Some Article</h1>
        <h2>This is some article about something.</h2>
        <script type="application/ld+json">
        {
            "@context": "https:\/\/schema.org",
            "@type": "Article",
            name: Some Article,
            url: https://de.example.org/articles/some,
        ]
        </script>
        </body>
        </html>
        HTML;

    $schemaOrgObjects = SchemaOrg::fromHtml($html);

    expect($schemaOrgObjects)->toBeEmpty();
});

it('returns null if the schema.org object doesn\'t have a distinct type', function () {
    $html = <<<HTML
        <!DOCTYPE html>
        <html lang="de-AT">
        <head>
        <title>Hello world</title>
        </head>
        <body>
        <script type="application/ld+json">
        {
            "@context": "http://schema.org",
            "@type": ["CreativeWork", "Product"],
            "name" : "something",
            "productID": "123abc"
        }
        </script>
        </body>
        </html>
        HTML;

    $schemaOrgObjects = SchemaOrg::fromHtml($html);

    expect($schemaOrgObjects)->toBeEmpty();
});

test('you can pass it a PSR-3 LoggerInterface and it will log an error message for invalid JSON string', function () {
    $scriptBlockContent = <<<INVALIDJSON
        {
            "@context": "https:\/\/schema.org",
            "@type": "Article",
            name: Some Article,
            url: https://de.example.org/articles/some,
        ]
        INVALIDJSON;

    $html = <<<HTML
        <!DOCTYPE html>
        <html lang="de-AT">
        <head>
        <title>Some Article</title>
        </head>
        <body>
        <h1>Some Article</h1>
        <h2>This is some article about something.</h2>
        <script type="application/ld+json">{$scriptBlockContent}</script>
        </body>
        </html>
        HTML;

    $logger = new class implements LoggerInterface {
        /**
         * @var array<array<string|string[]>>
         */
        public array $messages = [];

        public function emergency(\Stringable|string $message, array $context = []): void
        {
            $this->log('emergency', $message, $context);
        }

        public function alert(\Stringable|string $message, array $context = []): void
        {
            $this->log('alert', $message, $context);
        }

        public function critical(\Stringable|string $message, array $context = []): void
        {
            $this->log('critical', $message, $context);
        }

        public function error(\Stringable|string $message, array $context = []): void
        {
            $this->log('error', $message, $context);
        }

        public function warning(\Stringable|string $message, array $context = []): void
        {
            $this->log('warning', $message, $context);
        }

        public function notice(\Stringable|string $message, array $context = []): void
        {
            $this->log('notice', $message, $context);
        }

        public function info(\Stringable|string $message, array $context = []): void
        {
            $this->log('info', $message, $context);
        }

        public function debug(\Stringable|string $message, array $context = []): void
        {
            $this->log('debug', $message, $context);
        }

        public function log($level, \Stringable|string $message, array $context = []): void
        {
            $this->messages[] = ['level' => $level, 'message' => $message, 'context' => $context];
        }
    };

    SchemaOrg::fromHtml($html, $logger);

    expect($logger->messages[0])->toBe([
        'level' => 'warning',
        'message' => 'Failed to parse content of JSON-LD script block as JSON: { "@context": "https:\/\/schema.org", ' .
            '"@type": "Article", name: Some Article, url: https://de.exampl',
        'context' => [],
    ]);
});

it('converts graph (arrays) of schema.org objects to spatie class instances', function () {
    $html = <<<HTML
        <!DOCTYPE html>
        <html lang="de-AT">
        <head>
        <title>Hello world</title>
        </head>
        <body>
        <script type="application/ld+json">
        {
            "@context":"https://schema.org",
            "@graph":[
              {
                 "@type":"Product",
                 "@id":"https://www.store.com/product/1",
                 "name":"My Product",
                 "url":"https://www.store.com/schema/product/1",
                 "description":"My product description",
                 "offers":[
                    {
                       "@type":"AggregateOffer",
                       "lowPrice":"19.99",
                       "highPrice":"25.99",
                       "offerCount":3,
                       "priceCurrency":"EUR",
                       "availability":"http://schema.org/InStock",
                       "url":"https://www.store.com/my-product",
                       "itemCondition":"https://schema.org/NewCondition",
                       "@id":"https://www.store.com/schema/aggregate-offer/1",
                       "offers":[
                          {
                             "@type":"Offer",
                             "@id":"https://www.store.com/schema/offer/1",
                             "name":"My Product - Size S",
                             "url":"https://www.store.com/my-product/?size=s",
                             "priceSpecification":{
                                "@type":"PriceSpecification",
                                "price":"19.99",
                                "priceCurrency":"EUR"
                             }
                          },
                          {
                             "@type":"Offer",
                             "@id":"https://www.store.com/schema/offer/2",
                             "name":"My Product - Size M",
                             "url":"https://www.store.com/my-product/?size=m",
                             "priceSpecification":{
                                "@type":"PriceSpecification",
                                "price":"20.99",
                                "priceCurrency":"EUR"
                             }
                          },
                          {
                             "@type":"Offer",
                             "@id":"https://www.store.com/schema/offer/3",
                             "name":"My Product - Size L",
                             "url":"https://www.store.com/my-product/?size=l",
                             "priceSpecification":{
                                "@type":"PriceSpecification",
                                "price":"25.99",
                                "priceCurrency":"EUR"
                             }
                          }
                       ]
                    }
                 ]
              }
              ]
           }
        </script>
        </body>
        </html>
        HTML;

    $schemaOrgObjects = SchemaOrg::fromHtml($html);

    expect($schemaOrgObjects[0])->toBeInstanceOf(Product::class)->
        and($schemaOrgObjects[0]->getProperty('offers'))->toBeArray()->
        and($schemaOrgObjects[0]->getProperty('offers'))->toHaveCount(1)->
        and($schemaOrgObjects[0]->getProperty('offers')[0])->toBeInstanceOf(AggregateOffer::class)->
        and($schemaOrgObjects[0]->getProperty('offers')[0]->getProperty('offers'))->toBeArray()->
        and($schemaOrgObjects[0]->getProperty('offers')[0]->getProperty('offers'))->toHaveCount(3)->
        and($schemaOrgObjects[0]->getProperty('offers')[0]->getProperty('offers')[0])->toBeInstanceOf(Offer::class)->
        and($schemaOrgObjects[0]->getProperty('offers')[0]->getProperty('offers')[2])->toBeInstanceOf(Offer::class);
});

it('works correctly when the @graph property contains only a single object instead of an array', function () {
    $html = <<<HTML
        <!DOCTYPE html>
        <html lang="de-AT">
        <head>
        <title>Hello world</title>
        </head>
        <body>
        <script type="application/ld+json">
        {
            "@context": ["https://schema.org", {
                "csvw": "http://www.w3.org/ns/csvw#"
            }],
            "@graph": {
                "@type": "Dataset",
                "@id": "https://www.example.com/#/schema/DataSet/example.com/1",
                "name": "Example",
                "description": "Staafdiagram met de review-gegevens van Example, namens Example.",
                "publisher": {
                    "@id": "https://www.example.com/#/schema/Organization/1"
                },
                "about": {
                    "@id": "https://www.example.com/#/schema/Organization/example.com"
                },
                "mainEntity": {
                    "@type": "csvw:Table",
                    "csvw:tableSchema": {
                        "csvw:columns": [{
                            "csvw:name": "1 ster",
                            "csvw:datatype": "integer",
                            "csvw:cells": [{
                                "csvw:value": "50510",
                                "csvw:notes": ["15%"]
                            }]
                        }, {
                            "csvw:name": "2 sterren",
                            "csvw:datatype": "integer",
                            "csvw:cells": [{
                                "csvw:value": "9167",
                                "csvw:notes": ["3%"]
                            }]
                        }, {
                            "csvw:name": "3 sterren",
                            "csvw:datatype": "integer",
                            "csvw:cells": [{
                                "csvw:value": "12156",
                                "csvw:notes": ["4%"]
                            }]
                        }, {
                            "csvw:name": "4 sterren",
                            "csvw:datatype": "integer",
                            "csvw:cells": [{
                                "csvw:value": "26663",
                                "csvw:notes": ["8%"]
                            }]
                        }, {
                            "csvw:name": "5 sterren",
                            "csvw:datatype": "integer",
                            "csvw:cells": [{
                                "csvw:value": "238733",
                                "csvw:notes": ["70%"]
                            }]
                        }, {
                            "csvw:name": "Totaal",
                            "csvw:datatype": "integer",
                            "csvw:cells": [{
                                "csvw:value": "337229",
                                "csvw:notes": ["100%"]
                            }]
                        }]
                    }
                }
            }
        }
        </script>
        </body>
        </html>
        HTML;

    $schemaOrgObjects = SchemaOrg::fromHtml($html);

    expect($schemaOrgObjects[0])->toBeInstanceOf(Dataset::class)->
        and($schemaOrgObjects[0]->getProperty('name'))->toBe('Example');
});
