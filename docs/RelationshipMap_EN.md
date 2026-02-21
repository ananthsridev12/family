# Relationship Map (English)

This map is used for FamilyTree 3.0 dictionary-driven naming.

- Father / Mother: direct parent
- Son / Daughter: direct child
- Brother / Sister: sibling (share father or mother)
- Paternal grandfather/grandmother: ancestor via father edge
- Maternal grandfather/grandmother: ancestor via mother edge
- Periyappa: father's elder brother
- Chithappa: father's younger brother
- Athai: father's sister
- Mama: mother's brother
- Brother-in-law / Sister-in-law (Machan variant): spouse side sibling relation

Cousin formula:
- cousin_level = min(X, Y) - 1
- removed = abs(X - Y)
- generation_difference = Y - X

Where:
- X = distance from Person A to LCA
- Y = distance from Person B to LCA