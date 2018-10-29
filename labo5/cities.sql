-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Machine: localhost
-- Genereertijd: 24 okt 2018 om 15:43
-- Serverversie: 5.5.44-0ubuntu0.14.04.1
-- PHP-versie: 5.5.9-1ubuntu4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Databank: `project15`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `cities`
--

CREATE TABLE IF NOT EXISTS `cities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `postcode` int(10) unsigned DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lon` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2766 ;

--
-- Gegevens worden uitgevoerd voor tabel `cities`
--

INSERT INTO `cities` (`id`, `name`, `postcode`, `lat`, `lon`) VALUES
(7, 'Aalst', 9300, 50.94, 4.04),
(71, 'Appelterre-Eichem', 9400, 50.83, 4.02),
(517, 'Deinze', 9800, 50.98, 3.53),
(521, 'Dendermonde', 9200, 51.03, 4.1),
(585, 'Eeklo', 9900, 51.18, 3.58),
(790, 'Gent', 9000, 51.05, 3.72),
(842, 'Gottem', 9800, 50.98, 3.53),
(1717, 'Ninove', 9400, 50.83, 4.02),
(1807, 'Ophasselt', 9500, 50.77, 3.89),
(1843, 'Oudenaarde', 9700, 50.84, 3.61),
(2037, 'Ronse', 9600, 50.75, 3.6),
(2234, 'Sint-Niklaas', 9100, 51.16, 4.15),
(2701, 'Wontergem', 9800, 50.98, 3.53);

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `city_connections`
--

CREATE TABLE IF NOT EXISTS `city_connections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_id` int(11) NOT NULL,
  `neighbour_id` int(11) NOT NULL,
  `access_walk` tinyint(1) NOT NULL,
  `access_bike` tinyint(1) NOT NULL,
  `access_drive` tinyint(1) NOT NULL,
  `distance` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3851 ;

--
-- Gegevens worden uitgevoerd voor tabel `city_connections`
--

INSERT INTO `city_connections` (`id`, `node_id`, `neighbour_id`, `access_walk`, `access_bike`, `access_drive`, `distance`) VALUES
(3791, 790, 521, 1, 1, 0, 26.6613),
(3792, 517, 7, 0, 1, 1, 35.9949),
(3793, 790, 2701, 1, 0, 1, 15.4028),
(3794, 2701, 1807, 1, 1, 1, 34.3993),
(3795, 521, 517, 0, 1, 0, 40.2683),
(3796, 517, 7, 1, 1, 1, 35.9949),
(3797, 585, 2234, 1, 0, 0, 39.8028),
(3798, 2037, 517, 1, 1, 1, 26.0424),
(3799, 7, 1717, 1, 0, 0, 12.3116),
(3800, 842, 2037, 0, 0, 1, 26.0424),
(3801, 1717, 585, 0, 1, 1, 49.6229),
(3802, 71, 7, 1, 0, 1, 12.3116),
(3803, 1717, 790, 1, 0, 1, 32.2534),
(3804, 2234, 2701, 1, 1, 1, 47.7204),
(3805, 1843, 1717, 0, 0, 1, 28.814),
(3806, 790, 2037, 1, 1, 1, 34.4036),
(3808, 71, 1807, 0, 0, 1, 11.3129),
(3809, 7, 1843, 0, 1, 1, 32.1458),
(3810, 1843, 1717, 1, 0, 0, 28.814),
(3811, 71, 2234, 1, 1, 0, 37.8054),
(3812, 790, 517, 1, 1, 0, 15.4028),
(3813, 517, 521, 0, 1, 0, 40.2683),
(3814, 7, 1717, 1, 0, 0, 12.3116),
(3816, 7, 1717, 0, 0, 1, 12.3116),
(3817, 1843, 517, 0, 1, 0, 16.547),
(3818, 842, 7, 0, 0, 1, 35.9949),
(3819, 1843, 521, 0, 1, 0, 40.3158),
(3820, 585, 2234, 1, 0, 1, 39.8028),
(3821, 71, 1717, 1, 0, 1, 0),
(3823, 7, 2701, 1, 0, 1, 35.9949),
(3824, 1843, 2701, 1, 0, 0, 16.547),
(3825, 1717, 1843, 0, 1, 1, 28.814),
(3827, 790, 2234, 0, 1, 1, 32.418),
(3828, 790, 7, 0, 1, 0, 25.5176),
(3829, 585, 1807, 0, 1, 0, 50.4928),
(3830, 2037, 2234, 1, 1, 0, 59.6872),
(3832, 1807, 790, 1, 0, 1, 33.3381),
(3834, 585, 7, 1, 1, 1, 41.7811),
(3835, 1843, 842, 1, 1, 0, 16.547),
(3836, 585, 1807, 0, 0, 1, 50.4928),
(3838, 1843, 71, 1, 0, 0, 28.814),
(3839, 790, 842, 0, 1, 1, 15.4028),
(3840, 585, 71, 1, 1, 1, 49.6229),
(3842, 1843, 1807, 0, 1, 0, 21.1595),
(3843, 1807, 521, 1, 0, 1, 32.4454),
(3844, 71, 2234, 1, 1, 0, 37.8054),
(3845, 521, 517, 1, 0, 1, 40.2683),
(3846, 1717, 2037, 0, 0, 1, 30.8343),
(3847, 2234, 1807, 1, 0, 0, 47.0333),
(3848, 2701, 2234, 1, 0, 1, 47.7204),
(3849, 1843, 1807, 0, 1, 1, 21.1595),
(3850, 2234, 2037, 1, 1, 1, 59.6872);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
