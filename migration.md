```bash
for term in $( wp term list authors --field="term_id" ); do
    wp term update authors $term
done;
```