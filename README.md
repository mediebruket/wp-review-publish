# wp-review-publish
Wordpress plugin for å publisere bokanbefalinger til Deichmans RDF-base.

## Status
November 2012: Pluginet testes internt av Deichman. Ikke produksjonsklart ennå!

## Hva
Pluginet skal gjøre det enkelt for bloggende biblioteker å kunne publisere anbefalinger direkte fra sine Wordpress-blogger til Deichmans RDF-store.

## Installasjon
For å installere pluginet må du ha FTP-tilgang til din Wordpress-installasjon, eller annen mulighet for å kunne laste opp filer til server. Opprett en mappe i `wp-content\plugins` og kopier filene `wp-review-publish.php` og `uninstall.php` til denne mappa. Pluginet vil nå dukke opp i listen over plugins/instikk og kan aktiveres.

*Merk*: Pluginet gjør bruk av CURL for å snakke med Deichmans RDF-base, og du må derfor ha CURL tilgjengelig på server og tilgjengelig for PHP. Dersom du ikke vet hvordan du gjør dette, kan du lese [denne guiden](http://www.tomjepson.co.uk/enabling-curl-in-php-php-ini-wamp-xamp-ubuntu/). Har du ikke mulighet for å installere CURL, vil pluginet allikevel fungere, bortsett fra at du bokanbefalingen ikke vil bli slettet fra Deichmans RDF-base når du sletter bokanbefalingen i bloggen din.

## Bruk
Bibliotek som ønsker å bidra med bokanbefalinger vil få utdelt en API-nøkkel av Deichman. Denne angis i konfigurasjonen til pluginet (Settings->Bokanbefalinger). Videre så veldger du 'bokanbefaling' istedenfor vanlig bloggpost i wordpress-admin - fyller inn noen obligatoriske felter i tillegg til tittel og tekst (teaser, ISBN) - og når du så trykker publisér, så vil anbefalingen også sendes til Deichman's åpne bokanbefalingsbase, til glede og nytte for hele biblioteknorge:)

Hvis du endrer eller sletter en bokanbefaling i Wordpress-bloggen, vil endringene også sendes til RDF-basen.

## Merknader/Begrensninger
+  Feltene `teaser`, `ISBN`, er obligatoriske. Teaser skal kunne fungere som ingress eller lignende, og egnet til å vises i lister, inforskjerm eller andre steder med begrenset plass.
+ `Målgruppe` og `anmelder` fyller du ut dersom du vil. Det presiseres at målgruppe her vil si målgruppen for selve *anbefalingen*, og ikke om boken er for voksne eller barn.
+ Dersom Deichmans katalog ikke har en oppføring på ISBN-nummeret i bokanbefalingen, vil den ikke kunne skrives til RDF-basen. Dette er en begrensning vi jobber med å finne løsninger på.

## Hjelp?
Er det andre uklarheter så ikke nøl med å ta kontakt! Vi hjelper deg igang :)