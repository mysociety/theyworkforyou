# 📖 Register of interests

The Register of Members Interests contains a list of disclosures MPs are required to make of financial interests or benefits which *“others might reasonably consider to influence his or her actions or words as a Member of Parliament”*.

With TheyWorkForYou, we work to to build on and rework the register to make it easy to access and understand.

We also work to encourage better data and stronger rules in line with public expectations - [read more about our WhoFundsThem work](https://www.mysociety.org/democracy/who-funds-them/).

## 👤By person

On each MP/MSP/MS/MLA page you will find a 📖 [Register of Interests](/mp/25353/keir_starmer/holborn_and_st_pancras/register) tab a reprinting of the official register, with additional analysis of how it has changed over time.

MPs additionally have a 🏛️ [2024 Election Donations](/mp/25353/keir_starmer/holborn_and_st_pancras/election_register) - an enhanced version of the September 2024 register, with extra analysis and information on corporate donors and gifts.

## 📒Interests by category

For the UK Parliaments and Assemblies you can view the latest version of the register by category:

- [UK House of Commons](/interests/category?chamber=house-of-commons)
- [Scottish Parliament](/interests/category?chamber=scottish-parliament)
- [Senedd / Welsh Parliament](/interests/category?chamber=senedd)
- [Northern Ireland Assembly](/interests/category?chamber=northern-ireland-assembly)


## 🔍Highlighted interests

[View the highlighted interests page](/interests/highlighted_2024).

As part of our [WhoFundsThem](https://www.mysociety.org/democracy/who-funds-them/) project, we have highlighted interests that meet certain criteria (industries with low public favourability), and [brought them together in one place](/interests/highlighted_2024).

MPs have been given a chance to add comments, to add context around the donations.

You can read more about our research and choices in the accompanying [Beyond Transparency report](http://research.mysociety.org/html/beyond-transparency/).

## 📝 Spreadsheets

We publish several spreadsheet versions of the register of interests for different Parliaments. Read the notes to see which spreadsheet is best for your purpose.

- 📝 [UK House of Commons Register of Members' Financial Interests](https://pages.mysociety.org/parl_register_interests/datasets/commons_rmfi/latest)
    - Reformat of the [new bulk data release](https://publications.parliament.uk/pa/cm/cmregmem/contents2425.htm) of the UK House of Commons Register of Members’ Financial Interests.
    - Adds TWFY IDs and parties.
    - Only covers current version of the register.
    - Different sheets cover different kinds of interests (with seperate fields).
- 📝 [Register of Interests (2024-)](https://pages.mysociety.org/parl_register_interests/datasets/parliament_2024/latest)
    - Register of members' interests for 2024 Parliament.
    - Updates with new information, but retains expired interests. 
    - All interest details in single free_text cell.
    - Basic NLP processing to extract org and sum information. 
- 📝 [All registers datasets](https://pages.mysociety.org/parl_register_interests/datasets/all_registers_database/latest)
    - Combination of all parliamentary registers in a single dataset (including the first and last register an entry was published in).
    - The most 'database' export - with a seperate 'details' table for automated querying rather than lots of columns. 
- 📝 [Scottish Parliament Register of Interests](https://pages.mysociety.org/parl_register_interests/datasets/scottish_parliament_register_of_interests/latest)
    - Spreadsheet download of the Scottish Parliament's register. Single free text column only.
- 📝 [Senedd Register of Interests](https://pages.mysociety.org/parl_register_interests/datasets/senedd_register_of_interests/latest)
    - Spreadsheet download of the Senedd's register. Does not cover data not published before March 2025.
- 📝 [Northern Ireland Assembly Register of Interests](https://pages.mysociety.org/parl_register_interests/datasets/northern_ireland_assembly_register_of_interests/latest)
    - Spreadsheet download of the Northern Ireland Assembly's register.

Historical spreadsheets:

- 📝 [Register of Interests (2019-2024)](https://pages.mysociety.org/parl_register_interests/datasets/parliament_2019/latest)
- 📝 [Register of Members Interests (2000-)](https://pages.mysociety.org/parl_register_interests/datasets/all_time_register/latest) - Register of members' interests with basic NLP extraction.

We want to continue to improve our approach here – and [welcome feedback](https://survey.alchemer.com/s3/6876792/Data-usage?dataset_slug=parliament_2019&download_link=https%3A%2F%2Fpages.mysociety.org%2Fparl_register_interests%2Fdatasets%2Fparliament_2019%2F0_1_0) from anyone these spreadsheets helps.


## 📊 Data explorer

This data can also be explored [through Datasette](https://data.mysociety.org/datasette/?mysoc=parl_register_interests/parliament_2019/latest#/parliament_2019/register_of_interests), which can be used to query the datasets in the browser, and save the queries as links that can be shared.

For each of the above datasets, there is a Datasette link to explore the results. 

For instance, the following links go to specific queries (we’re using an in-browser version for prototyping and this might take a minute to load):

- [Paid visits to outside UK mentioning the UAE](https://data.mysociety.org/datasette/?mysoc=parl_register_interests/parliament_2024/latest#/parliament_2024/register_of_interests?category_name__exact=Visits+outside+the+UK&free_text__contains=UAE&_sort_desc=declared_in_latest)
- [Gifts from England Lawn Tennis Club](https://data.mysociety.org/datasette/?mysoc=parl_register_interests/parliament_2024/latest#/parliament_2024/register_of_interests?_filter_column_1=free_text&_filter_op_1=contains&_filter_value_1=Lawn+Tennis+Club&_filter_column=&_filter_op=exact&_filter_value=&_sort=rowid)
- [Declarations involving a helicopter](https://data.mysociety.org/datasette/?mysoc=parl_register_interests/parliament_2024/latest#/parliament_2024/register_of_interests?_filter_column_1=&_filter_op_1=exact&_filter_value_1=&_filter_column_2=free_text&_filter_op_2=contains&_filter_value_2=helicopter&_filter_column=&_filter_op=exact&_filter_value=&_sort=declared_in_latest&_sort_by_desc=on)
- [Declarations new in latest release](https://data.mysociety.org/datasette/?mysoc=parl_register_interests/parliament_2024/latest#/parliament_2024/register_of_interests?_sort=new_in_latest&_facet=new_in_latest&new_in_latest=1)


## 💾 Raw data

Our raw data is avaliable for download. The json files can be explored at:

https://www.theyworkforyou.com/pwdata/scrapedjson/universal_format_regmem/

Or downloaded with rsync:

```
rsync -az --progress --exclude '.svn' --exclude 'tmp/' --relative data.theyworkforyou.com::parldata/scrapedjson/universal_format_regmem/ .
```