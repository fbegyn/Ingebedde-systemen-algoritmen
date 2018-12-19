---
title: Slim door Gent
author: Benny Reubens en Francis Begyn
---

# Inleiding

Laat ons even een probleem schetsen:
Parkeerplaatsen in steden worden alsmaar schaarser en moeilijker te verkrijgen.
Dit is deels te verklaren door het feit dat navigatiesystemen alleen gebouwd
zijn om de gebruiker van punt naar punt te brengen (rekening houdend enkel met begin/eindpunt) . Bijgevolg calculeren deze systemen niet in dat de gebruiker het vervoermiddel erna nog moet parkeren.
Daarnaast willen niet alle gebruikers het dichtst mogelijk bij de bestemming staan omdat parkeerplaats duur is en/of men zeer lang moet zoeken.  Er zijn gebruikers die zo goedkoop mogelijk willen parkeren of het niet hinderlijk vinden om hiervoor even te wandelen. 

De misconceptie bestaat dat de auto vaak het snelste vervoersmiddel is, maar indien het zoeken meer tijd in beslagneemt dan de tijdswinst is een ander vervoersmiddel misschien adequater. Dit berekenen en de gebruiker navigeren naar de beste parkeerplaats is de opzet van dit project.
Dit project toont aan dat een parkeerplaats zoeken voor een wagen soms leidt tot tijdverlies, tijd die niet verloren gegaan zou zijn moest men een alternatief vervoermiddel gekozen hebben. Hieruit blijkt dat de luxe die men denk te verkrijgen door gebruik van een wagen, vaak een tijdskost met zich meebrengt die men gewoon aanvaardt.


# Opzet project

Er zal een webapplicatie geschreven worden die in Gent parkeerplaatsen suggereert gebaseerd op enkele parameters: afstand tot bestemming, drukte, tijdstip en het gekozen vervoermiddel(deze parameters dienen handmatig ingegeven te worden).
Hierbij is gebruik gemaakt  van beschikbare open databronnen zoals het
Gent open dataplatform, OpenStreetMap (hoofdzakelijk). 
Statechart

Zoals te zien is op afbeelding 1 bestaat het project uit 2 hoofdprocessen. Een inlog gedeelte en een navigeer gedeelte. In de uiteindelijke versie is het inloggedeelte weggelaten wegens tijdgebrek. De gedachte achter het navigeer gedeelte is redelijk straight forward achtereenvolgens gebeuren volgende stappen
1- Niets, totdat de gebruiker een geldige input geeft aka een juist adres (in het project zal dit via muisclick gebeuren)
2- Indien hij een juist adres heeft haalt hij zijn huidige locatie op en berekend de weg naar de dichtsbijzijnde parkeergelegenheid rekeninghoudend met de parameters.
3-Hierna volg een korte staat die de locatie toont + de weg erna toe
4- In het navigatie gedeelte checkt hij of de bestemming bereikt is en dat hij het getoonde beeld op



# Functies

Er is een frontend in javascript (met behulp van het React framework). Deze frontend zorgt voor de mapweergave, dat de gebruiker het eind en startpunt kan kiezen. Standaard wordt de locatie van de gebruiker gebruikt als startpunt, deze kan echter veranderd worden door te slepen. Deze bestanden kunnen teruggevonden worden in client/src pad. Het client pad bezit ook de overige configuratiebestanden voor React en de javascript.
De backend is in PHP geschreven en zorgt voor de uitvoering van het algoritme, de overige berekeningen en de databank operaties. Deze bestanden bevinden zich in server/php pad. In dit pad bevindt zich ook het init.php bestand. Dit bestand is een script die eigenlijk een goede databank zou moeten aanmaken om te gebruiken. Dit is niet afgeraakt en dus nog een work in progress. Het probleem dat ik tegenkom is dat er te weinig geheugen is om alle row entries aan te maken in de databank.

### PHP
	

function distance($lat1,$lon1,$lat2,$lon2)
	Berekend  afstand tussen 2 nodes
function getNode($nodeId)
	Haalt 1 node uit de databank
function getNodes($nodeId)
 	Zoekt de nodes op die kleiner zijn de gegeven node en slaat deze op in een 
 	array. De offset tussen beidet kan ingesteld worden.
function getNodeCache($id, $cache)
	Haalt de node die overeenkomt met id uit het cachegeheugen. Deze wordt 
 	uiteindelijk niet gebruikt, omdat het uitlezen vanuit de databank even  
 	snel ging (zie verder) 
function nodeDist($node1, $node2)
Haalt 2 nodes uit de databank en bepaald de afstand tussen deze twee
function compareNodes($a, $b)
 	Kijkt of 2 functies gelijk zijn
function getNodeId($from_lat, $from_lon, $transport)
 	Haalt de node op die het dichtst gelegen is bij een gegeven punt voor een  
 	gegeven transportmiddel.
	Er wordt per vervoermiddel gedifferentieerd door met de “case” functie te 
 	werken telkens rekeninghoudend of de node toegankelijk is voor het 
 	desbetreffende
function getNeigh($nodeId, $transport)
	bepaald alle buren van een gegeven node en slaat de kost op er naar toe te 
 	gaan op.
function buildPath($cameFrom, $min)
	Maakt een aaneenschakeling van nodes door telkens de dichtstbijzijnde op te 
 	slaan in een array en deze achterstevoren uit te schrijven
function getAStar($start, $end, $transport)
 	Deze functie voer thet A* algoritme uit. A* is een algoritme dat Dijkstra’s algoritme uitbreid met een extra kostenfactor. Het algoritme start met 1 knoop in de open set, de start knoop. Daarna zoekt het algoritme in de open set een knoop met de kleinste kostenfactor, en selecteert deze knoop als huidige knoop. Het verwijderd de huidige knoop uit de open set en plaatst deze in de gesloten set.
Daarna kijkt het of de huidige knoop de eindknoop is, en zo ja, stopt het algoritme hierna. Als de huidige knoop niet de eindknoop is, zoekt het algoritme de buren van de huidige knoop op, controleert of de buren niet in de gesloten of de open set zitten. Als de buur in de gesloten set zit, wordt deze overgeslagen. Als deze nog niet in de open set zit, wordt deze toegevoegd aan de open set en wordt de kostenfactor van de buur berekend. Nadat dit proces voor alle buren gepasseerd is, selecteert men opnieuw een knoop uit de open set (de knoop met de minimale kostenfactor).

Hier is het belangrijk om goede datastructuren te kiezen voor de open en gesloten set, alsook voor de structuur die kostenfactor moet bijhouden. Deze structuren ondergaan namelijk veel leesopdrachten en relatief weinig schrijfopdrachten, het is dus uiterst belangrijk dat deze structuren hier goed mee omkunnen. De standaard datastructuren in PHP kunnen dit echter niet.
Daarnaast zal het algoritme vertragen naarmate men verder in de zoektocht komt. De open en gesloten set zullen nu eenmaal alleen maar blijven groeien, wat de opzoekfuncties alleen maar trager en trager maken.

### Javascript

Er is gebruik gemaakt van het React framework voor de ontwikkeling van de frontend. Hiervoor wordt een klasse App aangemaakt dat een eigenschappen erft van de superklasse Component die door React aangeboden wordt.
In deze klasse kan men functies definiëren en aanmaken die de eigenschappen van dit object definiëren. Elke klasse kan ook een render() functie hebben, deze functie genereert de HTML code voor deze objecten.

Allereerst zetten we onze kaart goed, ingezoomd op Gent.
Daarna detecteren we muisklikken. De eerste is het startpunt de 2de het eindpunt. Het startpunt kan veranderd worden door het te verslepen, het eindpunt door op een andere plaats te klikken.
Hierna wordt afhankelijk van welke parameter geselecteerd is het event van het juiste vervoersmiddel getriggerd (te voet, met de fiets, met de auto)

In de testfase werden deze telkens afzonderlijk getriggerd, in het eindresultaat worden deze naast elkaar getoond. Op dit moment worden ze enkel naast elkaar getoond in een verder stadium zou enkel de snelste/kortste kunnen afgebeeld worden. Nu beslist de gebruiker zelf welke te gebruiken.

Verder bevat deze file nog een functie om de marker up te daten (indien de persoon zelf verplaatst). Om dan uiteindelijk de code te hebben die de afbeelding zal renderen en zichtbaar maken voor de gebruiker.
Resultaat
Onderstaande afbeelding heeft 1 resultaat weer van de applicatie. De blauwe lijn heeft een pad weer voor voetgangers, de rode lijn duidt het pad aan voor autos naar de dichtbijzijndste parkeerplaats. Volgens de databank is dit het ICC, dit is echter een fout in de databank aangezien het ICC geen publiek toegankelijke parking heeft. hier dient dus een correctie te gebeuren.
Alsook ontbreekt het pad voor de fietsstalling hier, dit kan veroorzaakt worden door 2 redenen:
Er is geen fietsstalling gevonden in de buurt: Indien men geen fietsstalling vindt in de buurt van het gekozen punt, heeft men geen eindbestemming en dus ook geen pad weer. Dit kan verholpen worden door een check uit te voeren of er een fietsstalling gevonden is.
Er is geen pad voor fietsers naar de eindbestemming: indien er geen pad beschikbaar is wordt enkel het eindpunt weergeven door de php code.
Het vermoeden is hier dat er geen pad gevonden wordt, aangezien er wel degelijk fietsstallingen nabij de Ledeganck in de databank staan.

De volgende afbeelding toont een resultaat voor een fietsroute een een wandel route. Hier kan voor de wagen geen parking gevonden in de geselecteerde omgeving, waardoor er dus ook geen pad aangemaakt wordt.

# Performantie / verbeteringen / problemen

Geheugen
Het eerste gebruikte algoritme is het algoritme van Dijkstra. Dit algoritme werd overgenomen uit de practica voor dit vak, waarna er aanpassing werden gedaan om het geheugengebruik te optimaliseren.
De eerste implementatie was namelijk heel geheugen inefficiënt. Alle nodes werden in het geheugen geladen, waarna het pad berekend werd. Dit leverde een snelle oplossing op (10 / 12 seconden), echter is deze niet schaalbaar. Als men deze implementatie zou gebruiken op een grotere schaal zou deze namelijk veel resources beginnen gebruiken en zou de server vastlopen of het programma crashen.

De optimalisatie die toegepast werd is om enkel de nodes in te laden die geëvalueerd worden. Hierdoor laadt men enkel de nodes in het geheugen die men nodig heeft voor het algoritme. Dit levert als voordeel op dat het enigste dat men moet bijhouden 2 sets en 2 tabellen zijn: 1 set -open- voor de “open” nodes, 1 set -closed- voor de “gesloten” nodes, 1 tabel - G - voor het bijhouden van de “kost” om tot elke node te geraken en dan nog een tabel - cameFrome - met voor elke node wat de goedkoopste knoop is om tot aan die knoop te geraken.

Deze optimalisatie is blijven bestaan toen het A-star algoritme werd geïmplementeerd, hierbij moet men een extra tabel bijhouden met de kost om van de huidige knoop naar de bestemming te geraken. Deze tabel is genaamd - F - in ons algoritme.
Deze kost, F, is opgesteld door bij de kost G een heuristiek bij te tellen (in ons geval, de euclidische afstand van de huidige knoop tot de eindbestemming.

Snelheid
Het gevolg van de geheugenoptimalisatie is dat die voor een vertraging zorgt in het algoritme (ongeveer 17 seconden). Door deze beslissingen moeten er namelijk veel databank operaties uitgevoerd worden. Hiervoor werd dan gekeken om gedeeltelijke caches te implementeren. Bij het testen hiervan, blijkt dat de databank operaties maar een kleine vertraging opleveren (2 seconden). Het probleem ligt dus niet noodzakelijk in het aantal operaties.

Na een beetje onderzoek blijkt dat de databank operatie op zichzelf gewoon heel lang inneemt. Men kan echter wel door de databank te indexeren deze operaties aanzienlijk versnellen. Het commando `create (unique) index … ` zorgt ervoor dat er indexen gemaakt worden in de databank. Deze indexen versnellen de databank operaties aanzienlijk. Na het invoeren van deze indexen wordt het algoritme uitgevoerd in 4 / 5 seconden.

Fouten in de databank
De databank die we gebruiken is niet foutloos en soms wordt er geen pad gevonden, terwijl er wel een pad hoort te zijn (als men op de kaart kijkt). Dit komt waarschijnlijk door het verkeerd aanmaken van de osm_node_neighours databanken die we gebruiken. Hierin ontbreken bepaalde essentiële paden die ervoor zorgen dat het algoritme stopt en er uiteindelijk geen pad gevonden wordt.

Daarnaast ontbreken ook een groot aantal parkeerplaatsen in de aangemaakt databanken. Het vermoeden is dat dit komt door de manier waarop parkeerplaatsen kunnen voorkomen in OpenStreetMap. een parkeerplaats kan aangeduid worden als een punt, echter kunnen parkeerplaatsen ook aangeduid worden als zones (een collectie punten). Dit onderscheidt, samen met de verschillende benamingen (amenity tags) die bestaan (parking / car_parking, bicycle_parking / bike_parking) zorgt er volgens ons voor dat bepaalde parkeerplaatsen ontbreken.

