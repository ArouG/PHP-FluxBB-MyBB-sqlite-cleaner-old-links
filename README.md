Lors d'une importation de base d'un forum FluxBB vers un forum MyBB, MERGE SYSTEM perd la concordance des identificateurs des tables users, forums, topics / threads et posts. Par ailleurs, le simple ajout de Mycode / BBcode dans le nouveau forum ne suffit donc pas à récupérer les anciens liens natifs de FluxBB que sont [user=uid]...[/user], forum, topic et post.

nettoit.php a pour objectif de nettoyer la table posts de la base sqlite3 de MyBB afin de conserver ces vieux liens là.
