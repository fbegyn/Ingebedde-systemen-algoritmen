# Slim door Gent

Dit project is opgebouwd uit 2 delen:

* Frontend: Geschreven in Javascript met React. Als npm geïnstalleerd is op de server kan men in de `./Software/client`
  folder na het command `npm run install` uitgevoerd te hebben, een lokale development server opstarten met `npm start`.
  Wanneer men een website wil builden om te deployen op een webserver, kan men `npm run build` gebruiken. Dit commando
  plaatst bestanden in een `build` folder, deze bestanden kunnen dan op een webserver geplaatst worden.

* Backend: dit zijn php bestanden die door een webservergehost moeten worden. Deze webserver moet php ondersteunen. Als
  php op het systeem geinstalleerd is kan men deze bestanden hosten met `php -S localhost:3001` waarna deze bestanden
  beschikbaar worden op `http://localhost:3001`.
