# wp-review-publish
Wordpress plugin for å publisere bokanbefalinger til Deichmans RDF-base.

## Hva
Pluginet skal gjøre det enkelt for bloggende biblioteker å kunne publisere anbefalinger direkte fra sine Wordpress-blogger til Deichmans RDF-store.

## Installasjon
For å installere pluginet må du ha FTP-tilgang til din Wordpress-installasjon. Opprett en mappe i `wp-content\plugins` og kopier filene `wp-review-publish.php` og `uninstall.php` til denne mappa. Pluginet vil nå dukke opp i listen over plugins/instikk og kan aktiveres.

## Bruk
Bibliotek som ønsker å bidra med bokanbefalinger vil få utdelt en API-nøkkel av Deichman. Denne angis i konfigurasjonen til pluginet (Settings->Bokanbefalinger). Videre så veldger du 'bokanbefaling' istedenfor vanlig bloggpost i wordpress-admin - fyller inn noen obligatoriske felter (teaser, ISBN) - og når man så trykker publisér, vil anbefalingen også sendes til Deichman's åpne bokanbefalingsbase, til glede og nytte for hele biblioteknorge:)

Hvis du endrer eller sletter en bokanbefaling i Wordpress-bloggen, vil endringene også sendes til RDF-basen.