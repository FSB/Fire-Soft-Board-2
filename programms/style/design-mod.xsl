<?xml version="1.0" encoding="ISO-8859-1" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"> 
	<xsl:output method="html"/>
	<xsl:variable name="upper">ABCDEFGHIJKLMNOPQRSTUVWXYZ</xsl:variable>
	<xsl:variable name="lower">abcdefghijklmnopqrstuvwxyz</xsl:variable>
	<xsl:template match="/">
	<html>
	<head>
		<title><xsl:value-of select="mod/header/name" /></title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<link type="text/css" rel="stylesheet" href="style/design-mod.css" />
	</head>
	<body>
		<xsl:apply-templates select="mod" />
	</body>
	</html>
	</xsl:template>

	<xsl:template match="mod">
		<div id="header">
			<h1>
				<xsl:value-of select="header/name" />
				<xsl:choose>
					<xsl:when test="count(header/isUpdate) > 0"><div id="isUpdate">Ceci est une mise à jour du MOD <xsl:value-of select="header/isUpdate/@parent" /></div></xsl:when>
				</xsl:choose>
			</h1>
			<table style="width: 100%">
				<tr>
					<td style="width: 50%; vertical-align: top">
						<ul>
							<li><b>Version : </b><xsl:value-of select="header/version" /></li>
							<li><b>Description : </b><xsl:value-of select="header/description" /></li>
							<li><b>Requêtes : </b>
								<xsl:choose>
									<xsl:when test="count(instruction/line/@name[. = 'sql' or . = 'requete sql']) > 0">Oui</xsl:when>
									<xsl:otherwise>Non</xsl:otherwise>
								</xsl:choose>
							</li>
							<li><b>Fichiers joints : </b><xsl:value-of select="count(instruction/line[@name = 'Copier' or @name = 'copy']/file)" /></li>
							<li><b>Fichiers modifiés : </b><xsl:value-of select="count(instruction/line[@name = 'Ouvrir' or @name = 'open'])" /></li>
						</ul>
					</td>
					<td style="width: 50%; vertical-align: top">
						<xsl:for-each select="header/author">
							<ul>
								<li><b>Auteur : </b><xsl:value-of select="name" /></li>
								<li><b>Email : </b><a href="mailto:{email}"><xsl:value-of select="email" /></a></li>
								<li><b>Site Web : </b><a href="{website}"><xsl:value-of select="website" /></a></li>
							</ul>
						</xsl:for-each>
					</td>
				</tr>
			</table>

			<xsl:choose>
				<xsl:when test="count(header/note) > 0"><div id="note"><b>Notes :</b><br /><xsl:value-of select="header/note" /></div></xsl:when>
			</xsl:choose>
		</div>

		<div id="left">
			<b>Fichiers modifiés :</b><ul>
			<xsl:for-each select="instruction/line">
				<xsl:choose>
					<xsl:when test="./@name = 'open' or ./@name = 'Ouvrir'">
						<li><a href="#open{position()}"><xsl:value-of select="file" /></a></li>
					</xsl:when>
				</xsl:choose>
			</xsl:for-each>
			</ul>
		</div>

		<div id="right">
			<div class="desc"><h1>Installation manuelle</h1>Vous pouvez installer ces modifications via l'installeur de modules de FSB2 (Administration -> général -> modules -> installation), ou bien l'installer manuellement en suivant exactement les instructions ci dessous.</div>
			<xsl:for-each select="instruction/line">
				<xsl:choose>
					<xsl:when test="translate(./@name, $upper, $lower) = 'open' or translate(./@name, $upper, $lower) = 'ouvrir'">
						<br /><br /><hr /><h2><a name="open{position()}"></a>[<xsl:number value="position()" format="1" />] OUVRIR le fichier : <xsl:value-of select="file" /></h2>
					</xsl:when>
					
					<xsl:when test="translate(./@name, $upper, $lower) = 'chercher' or translate(./@name, $upper, $lower) = 'trouver' or translate(./@name, $upper, $lower) = 'dans la ligne chercher' or translate(./@name, $upper, $lower) = 'dans la ligne trouver' or translate(./@name, $upper, $lower) = 'search' or translate(./@name, $upper, $lower) = 'find' or translate(./@name, $upper, $lower) = 'in line search' or translate(./@name, $upper, $lower) = 'in line find'">
						<h3>[<xsl:number value="position()" format="1" />] Chercher les lignes suivantes :</h3>
						<textarea cols="100" rows="10" onfocus="select()"><xsl:value-of select="code" /></textarea>
					</xsl:when>
					
					<xsl:when test="translate(./@name, $upper, $lower) = 'apres ajouter' or translate(./@name, $upper, $lower) = 'ajouter apres' or translate(./@name, $upper, $lower) = 'after' or translate(./@name, $upper, $lower) = 'after add'">
						<h3>[<xsl:number value="position()" format="1" />] Ajouter, après les lignes cherchées :</h3>
						<textarea cols="100" rows="10" onfocus="select()"><xsl:value-of select="code" /></textarea>
					</xsl:when>

					<xsl:when test="translate(./@name, $upper, $lower) = 'dans la ligne ajouter' or translate(./@name, $upper, $lower) = 'ajouter dans la ligne' or translate(./@name, $upper, $lower) = 'in line add'">
						<h3>[<xsl:number value="position()" format="1" />] Ajouter, après les lignes cherchées :</h3>
						<textarea cols="100" rows="10" onfocus="select()"><xsl:value-of select="code" /></textarea>
					</xsl:when>
					
					<xsl:when test="translate(./@name, $upper, $lower) = 'avant ajouter' or translate(./@name, $upper, $lower) = 'ajouter avant' or translate(./@name, $upper, $lower) = 'before' or translate(./@name, $upper, $lower) = 'before add'">
						<h3>[<xsl:number value="position()" format="1" />] Ajouter, avant les lignes cherchées :</h3>
						<textarea cols="100" rows="10" onfocus="select()"><xsl:value-of select="code" /></textarea>
					</xsl:when>

					<xsl:when test="translate(./@name, $upper, $lower) = 'remplacer par' or translate(./@name, $upper, $lower) = 'remplacer' or translate(./@name, $upper, $lower) = 'replace by' or translate(./@name, $upper, $lower) = 'replace'">
						<h3>[<xsl:number value="position()" format="1" />] Remplacer les lignes cherchées par :</h3>
						<textarea cols="100" rows="10" onfocus="select()"><xsl:value-of select="code" /></textarea>
					</xsl:when>

					<xsl:when test="translate(./@name, $upper, $lower) = 'supprimer' or translate(./@name, $upper, $lower) = 'delete'">
						<h3>[<xsl:number value="position()" format="1" />] Supprimer les lignes cherchées</h3>
					</xsl:when>

					<xsl:when test="translate(./@name, $upper, $lower) = 'copier' or translate(./@name, $upper, $lower) = 'copy'">
						<br /><br /><hr /><h2>[<xsl:number value="position()" format="1" />] Copier les fichiers</h2>
						<div class="desc">Copiez les fichiers ci dessous fournis dans le répertoire root/ du MOD, vers votre forum, en gardant les mêmes emplacements.</div>
						<ul>
							<xsl:for-each select="file">
								<li>~/root/<xsl:value-of select="filename" /><xsl:if test="directory">/*</xsl:if></li>
							</xsl:for-each>
						</ul>
					</xsl:when>

					<xsl:when test="translate(./@name, $upper, $lower) = 'requete sql' or translate(./@name, $upper, $lower) = 'sql' or translate(./@name, $upper, $lower) = 'sql query'">
						<br /><br /><hr /><h2>[<xsl:number value="position()" format="1" />] Exécuter les requête SQL</h2>
						<div class="desc">Exécutez ces requêtes SQL dans l'onglet Base de données de votre administration</div>
						<br />
						<textarea cols="100" rows="10">
							<xsl:for-each select="query"><xsl:value-of select="." />;
</xsl:for-each>
						</textarea>
						<br />
					</xsl:when>

					<xsl:when test="translate(./@name, $upper, $lower) = 'executer' or translate(./@name, $upper, $lower) = 'exec' or translate(./@name, $upper, $lower) = 'php'">
						<h2>[<xsl:number value="position()" format="1" />] Execution de code source :</h2>
						<xsl:if test="code">
							<div class="desc">Créez un fichier nommé <b>fsb_update.php</b> à la racine de votre forum, et copiez y le code source ci dessous. Rendez vous ensuite avec votre navigateur à la page suivante : <b>http://www.votredommaine.com/votreofrum/fsb_update.php</b> pour lancer le script.</div>
							<br />
							<textarea cols="100" rows="10" onfocus="select()">&lt;?php
<xsl:value-of select="code" />
echo 'Fichier executé. Veuillez supprimer ce fichier de votre forum, afin d\'éviter tout problème de sécurité';
?&gt;</textarea>
						</xsl:if>
						<xsl:if test="filename">
							<div class="desc">Veuillez vous rendre avec votre navigateur sur la page <b>http://www.votredommaine.com/votreforum/<i><xsl:value-of select="filename" /></i></b> pour lancer le script. Une fois le script lancé et son exécution terminée, supprimez le de votre forum.</div>
						</xsl:if>
					</xsl:when>
					
					<xsl:when test="translate(./@name, $upper, $lower) = 'fin' or translate(./@name, $upper, $lower) = 'fin du mod' or translate(./@name, $upper, $lower) = 'end' or translate(./@name, $upper, $lower) = 'end of mod'">
						<h2>Fin de l'installation</h2>
					</xsl:when>
				</xsl:choose>
			</xsl:for-each>

			<br /><br /><hr /><h2>Exécuter les requêtes SQL</h2>
			<div class="desc">Exécutez ces requêtes SQL supplémentaires dans l'onglet Base de données de votre administration</div>
			<br />
			<textarea cols="100" rows="10"><xsl:value-of select="header/manualQueries" /></textarea>
			<br />

			<br />
			<div id="copyright">Forum <a href="http://www.fire-soft-board.com">Fire Soft Board</a></div>
		</div>
	</xsl:template>
</xsl:stylesheet>