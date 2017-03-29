# simpleNaMiAPI

simpleNaMiAPI ist ein Versuch, eine einfache Schnittstelle für den Zugriff auf
die NaMi mithilfe von php zu realisieren. Sie soll als Grundlage für andere
Anwendungen dienen und umfasst deshalb nur einen minimalen Funktionsumfang.

## Entwicklungsstatus: Version 1.2

Vorläufig finale und stabile Version. Pläne zur Weiterentwicklung sind in der
[Roadmap](#roadmap) beschrieben.

## Über die NaMi

Die NaMi ist die namentliche Mitgliedermeldung der [Deutschen Pfadfinderschaft
St. Georg](http://dpsg.de). Mehr Informationen zur NaMi finden sich

* in der [Dokumentation](http://doku.dpsg.de) und
* auf der Seite des [NaMi Community Managements (ncm)](http://ncm.dpsg.de).

Das System hinter NaMi wird wohl auch von anderen Verbänden etc. eingesetzt.
Sollte simpleNaMiAPI mit den APIs anderer Lizenznehmer dieses
Mitgliederverwaltungssystems inkompatibel sein, würde ich mich über einen
entsprechenden Hinweis freuen.

## Systemvoraussetzungen
Die Voraussetzungen zum Einsatz von simpleNaMiAPI sind sehr gering gehalten.
Beispielsweise kommt simpleNaMiAPI ohne cURL aus. Außerdem wird vorerst auf
PHP-7-Features verzichtet. Genaue Systemanforderungen wurden bislang nicht
ermittelt.

## Funktionsumfang

Folgt.

## Lizenz

Ich würde mich freuen, wenn simpleNaMiAPI als Basis vieler NaMi-Projekte
verwendet würde. Deshalb habe ich sie unter die [Universal Permissive License
(UPL), Version 1.0](https://opensource.org/licenses/UPL)
[(Zusammenfassung)](https://tldrlegal.com/license/universal-permissive-license-1.0-(upl-1.0))
gestellt.

## Mitarbeit

Wer Bugs findet, ist herzlich dazu eingeladen, diese zu melden. Wer den Bug
gleich selbst beheben kann, darf auch gerne einen Pull Request erstellen.

## Roadmap

Vorerst sollen nur Bugfixes und Sicherheitsupdates durchgeführt werden.
Zusätzliche Funktionalität möchte ich nur implementieren, wenn davon auszugehen
ist, dass ein Großteil der auf simpleNaMiAPI aufbauenden Projekte diese
benötigt. Möglicherweise machen auch Änderungen an NaMi eine Weiterentwicklung
nötig.

Sobald sich PHP 7 durchgesetzt hat und bei den gängigsten Open-Source-Projekten
eingesetzt wird, möchte ich überprüfen, welche neuen Features zur Verbesserung
von SimpleNaMiAPI beitragen können.