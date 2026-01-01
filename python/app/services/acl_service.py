from typing import Optional, List
from app.models.user import User
from app.models.acl_entities import Role

class AclService:
    def check(self, user: User, entity_type: str, action: str) -> bool:
        """
        Checks if the user has permission to perform action on entity_type.
        This does NOT check specific record scope (ownership), just general access.
        For read/edit/delete/stream, if the level is not 'no', it returns True.
        But the caller must enforce scope if level is not 'all'.
        For create, it returns True or False.
        """
        if user.is_admin:
            return True

        level = self.get_permission_level(user, entity_type, action)

        if action == 'create':
            return level == 'yes'

        return level != 'no'

    def get_permission_level(self, user: User, entity_type: str, action: str) -> str:
        """
        Returns the permission level:
        - For boolean actions (create): 'yes', 'no'
        - For scoped actions (read, edit, delete, stream): 'all', 'team', 'own', 'no'
        """
        if user.is_admin:
            return 'yes' if action == 'create' else 'all'

        # Default to 'no'
        final_level = 'no'

        # Gather all roles
        roles = user.roles
        # TODO: Add roles from user's teams?
        # EspoCRM: "Roles can be assigned to Users and Teams."
        # So we need to fetch roles from user.roles AND user.teams.roles

        all_roles = list(roles)
        if user.teams:
            for team in user.teams:
                if team.roles:
                    all_roles.extend(team.roles)

        if not all_roles:
            return 'no'

        # Merge permissions
        for role in all_roles:
            role_data = role.data or {}
            entity_perms = role_data.get(entity_type, {})

            # If entity permissions not defined, maybe check 'Global' or default?
            # EspoCRM has no 'Global' fallback in role data usually, it's explicit.
            # But it depends on UI. Assuming explicit.

            perm = entity_perms.get(action)
            if perm:
                final_level = self._merge_levels(final_level, perm, action)

        return final_level

    def check_scope(self, user: User, entity, action: str) -> bool:
        """
        Checks if user has access to a SPECIFIC record based on their permission level.
        Assumes entity has 'assigned_user_id' and 'teams' (if generic).
        """
        if user.is_admin:
            return True

        # Determine entity type from class name?
        entity_type = entity.__class__.__name__
        # Or pass it? Passing is safer if proxy.

        level = self.get_permission_level(user, entity_type, action)

        if level == 'all':
            return True
        if level == 'no':
            return False

        # Check ownership
        is_owner = str(entity.assigned_user_id) == str(user.id)

        if level == 'own':
            return is_owner

        if level == 'team':
            # Check if user is in any of the entity's teams
            # Or if user is owner (usually implies access)
            if is_owner:
                return True

            # Use sets for intersection
            user_team_ids = {t.id for t in user.teams}
            # entity.teams might be a query or list.
            # If it's a relationship, accessing it might trigger query.
            entity_teams = entity.teams
            if not entity_teams:
                return False

            entity_team_ids = {t.id for t in entity_teams}

            if not user_team_ids.isdisjoint(entity_team_ids):
                return True

        return False

    def _merge_levels(self, current: str, new: str, action: str) -> str:
        """
        Merges two permission levels, returning the more permissive one.
        Order: all > team > own > no
        Boolean: yes > no
        """
        if action == 'create':
            return 'yes' if 'yes' in (current, new) else 'no'

        priority = {'all': 4, 'team': 3, 'own': 2, 'no': 1}

        # Handle invalid values gracefully
        current_p = priority.get(current, 0)
        new_p = priority.get(new, 0)

        return current if current_p >= new_p else new

acl_service = AclService()
