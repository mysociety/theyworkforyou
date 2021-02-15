'''

Python 3
Downloads thumbnails
of MPs from parliament API and wikipedia.

Does four passes:
1. Offical API
2. Wikipedia images where wikidata has a twfy_id
3. Wikipedia images where joined on name
4. Wikidata images where there is a twfy_id

4 overlaps with 2, but with older images so deprioritised. 

requires:

Pillow
everypolitician-popolo
wikipedia

'''

import csv
import json
import os
import re
from collections import Counter
from os.path import exists
from tempfile import gettempdir
from urllib.parse import unquote, urlparse
from urllib.request import urlopen, urlretrieve

import requests
import wikipedia
from PIL import Image
from popolo_data.importer import Popolo

small_image_folder = r"..\www\docs\images\mps"
large_image_folder = r"..\www\docs\images\mpsL"
official_image_folder = r"..\www\docs\images\mpsOfficial"
wikidata_image_folder = r"..\www\docs\images\mpsWikidata"

# query to get images stored in wikidata (should be used secondary to the wikipedia sources)
wikidata_query = """
SELECT DISTINCT ?person ?personLabel ?partyLabel ?twfy_id ?image {
 ?person p:P39 ?positionStatement .
 ?positionStatement ps:P39 [wdt:P279* wd:Q16707842] .  # all people who held an MP position
 ?person wdt:P18 ?image .
 ?person wdt:P2171 ?twfy_id . 
  
 SERVICE wikibase:label { bd:serviceParam wikibase:language 'en' }
}
ORDER BY ?start
"""

# query to make twfy_ids to wikipedia page via wikidata
wikidata_to_wikipedia_query = """
prefix schema: <http://schema.org/>
PREFIX wikibase: <http://wikiba.se/ontology#>
PREFIX wd: <http://www.wikidata.org/entity/>
PREFIX wdt: <http://www.wikidata.org/prop/direct/>

SELECT DISTINCT ?person ?twfy_id ?article WHERE {
     ?person p:P39 ?positionStatement .
     ?positionStatement ps:P39 [wdt:P279* wd:Q16707842] .
     ?person wdt:P2171 ?twfy_id . # with twfy id
    OPTIONAL {
      ?article schema:about ?person .
      ?article schema:inLanguage "en" .
      FILTER (SUBSTR(str(?article), 1, 25) = "https://en.wikipedia.org/")
    }
} 
"""

# query to make twfy_ids to wikipedia page via wikidata names
unided_wikipedia_query = """
prefix schema: <http://schema.org/>
PREFIX wikibase: <http://wikiba.se/ontology#>
PREFIX wd: <http://www.wikidata.org/entity/>
PREFIX wdt: <http://www.wikidata.org/prop/direct/>

SELECT DISTINCT ?person ?personLabel ?givenLabel ?familyLabel ?twfy_id ?article WHERE {
     ?person p:P39 ?positionStatement .
     ?positionStatement ps:P39 [wdt:P279* wd:Q16707842] .
     OPTIONAL { ?person wdt:P2171 ?twfy_id . } # with twfy id
     OPTIONAL { ?person wdt:P735 ?given . }
     OPTIONAL { ?person wdt:P734 ?family . }
    OPTIONAL {
      ?article schema:about ?person .
      ?article schema:inLanguage "en" .
      FILTER (SUBSTR(str(?article), 1, 25) = "https://en.wikipedia.org/")
    }
 SERVICE wikibase:label { bd:serviceParam wikibase:language 'en' }
} 
"""


def clean_name(name):
    return re.sub('[^A-Za-z0-9]+', '', name).lower()

WIKI_REQUEST = 'http://en.wikipedia.org/w/api.php?action=query&prop=pageimages&format=json&piprop=original&titles='


def get_image_from_wikipedia(page):
    """
    given a wikipedia page, get the main image
    """
    unescaped = unquote(page)
    wkpage = wikipedia.WikipediaPage(title=unescaped)
    title = wkpage.title
    response = requests.get(WIKI_REQUEST+title)
    json_data = json.loads(response.text)
    print("getting image url from wikipedia")
    try:
        img_link = list(json_data['query']['pages'].values())[
            0]['original']['source']
        return img_link
    except KeyError:
        return None


def get_wikipedia():
    """
    get wikipedia images based in twfy_ids in wikidata
    """
    url = 'https://query.wikidata.org/sparql'

    r = requests.get(
        url, params={'format': 'json', 'query': wikidata_to_wikipedia_query})
    data = r.json()
    for person in data["results"]["bindings"]:
        twfy_id = person["twfy_id"]["value"]
        if "article" in person:
            wikipedia_url = person["article"]["value"]
            wikipedia_title = wikipedia_url.split("/")[-1]
            print(wikipedia_title)
            image_url = get_image_from_wikipedia(wikipedia_title)
            if image_url:
                get_wiki_image(image_url, twfy_id)


def get_idless_wikipedia(override=False):
    """
    get images where there is a direct name but not id match in wikipedia
    """
    url = 'https://query.wikidata.org/sparql'
    twfy_name_to_id = get_name_to_id_lookup()
    print("getting query")
    r = requests.get(
        url, params={'format': 'json', 'query': unided_wikipedia_query})
    data = r.json()
    print("fetched query")
    for person in data["results"]["bindings"]:
        twfy_id = person.get("twfy_id", {"value": None})["value"]
        if twfy_id:
            continue
        full_label = person["personLabel"]["value"]
        given = person.get("givenLabel", {"value": ""})["value"]
        family = person.get("familyLabel", {"value": ""})["value"]
        joined = given + " " + family
        full_label = clean_name(full_label)
        alt_label = clean_name(joined)
        twfy_id = twfy_name_to_id.get(
            full_label, twfy_name_to_id.get(alt_label, None))
        if twfy_id and "article" in person:
            twfy_id = twfy_id.split("/")[-1]
            print(twfy_id, person["article"]["value"])
            wikipedia_url = person["article"]["value"]
            wikipedia_title = wikipedia_url.split("/")[-1]
            print(wikipedia_title)
            dest_path = os.path.join(
                wikidata_image_folder, "{0}.jpg".format(twfy_id))
            if override == False and os.path.exists(dest_path):
                print("downloaded, skipping")
                continue
            image_url = get_image_from_wikipedia(wikipedia_title)
            if image_url:
                get_wiki_image(image_url, twfy_id)


def get_wikidata():
    """
    download images stored in wikidata by twfy_id
    - lower priority as less well updated than wikipedia
    """
    url = 'https://query.wikidata.org/sparql'

    r = requests.get(url, params={'format': 'json', 'query': wikidata_query})
    data = r.json()
    for person in data["results"]["bindings"]:
        twfy_id = person["twfy_id"]["value"]
        image_url = person["image"]["value"]
        get_wiki_image(image_url, twfy_id)


def get_wiki_image(image_url, twfy_id, override=False):
    """
    given an image on wikipedia, download
    """
    ext = image_url.split(".")[-1]
    dest_ext = ext
    if dest_ext.lower() in ["gif"]:
        dest_ext = "jpg"
    if ext in ["svg", "pdf", "tif"]:  # coat of arms or something
        return None
    filename = "{0}.{1}".format(twfy_id, ext)
    official_filename = os.path.join(
        official_image_folder, "{0}.jpg".format(twfy_id))
    if os.path.exists(official_filename):
        return None
    temp_path = os.path.join(gettempdir(), filename)
    dest_path = os.path.join(wikidata_image_folder, filename)
    if override == False and os.path.exists(dest_path):
        return None
    urlretrieve(image_url, temp_path)
    print("downloaded: {0}".format(image_url))
    try:
        image = Image.open(temp_path)
    except Exception:
        return None
    image.thumbnail((260, 346))
    image.save(dest_path, quality=95)
    image.close()
    os.remove(temp_path)


def get_name_to_id_lookup():
    """
    create id lookup from popolo file
    where someone's name is unique
    try and map to twfy id
    """
    people_url = "https://github.com/mysociety/parlparse/raw/master/members/people.json"
    pop = Popolo.from_url(people_url)
    count = 0
    lookup = {}
    print("Creating name to id lookup")
    all_names = []

    def add_name(reduced_name, id):
        all_names.append(reduced_name)
        lookup[reduced_name] = id

    for p in pop.persons:
        id = p.id
        for name in p.other_names:
            possible_names = []
            if "given_name" in name and "family_name" in name:
                possible_names.append(
                    name["given_name"] + " " + name["family_name"])
            if "additional_name" in name:
                possible_names.append(name["additional_name"])

            # only add one copy of each reduced name
            possible_names = [clean_name(x).lower() for x in possible_names]
            possible_names = list(set(possible_names))
            for p in possible_names:
                add_name(p, id)

    # if the same name leads to multiple ids, delete
    c = Counter(all_names)
    for k, v in c.items():
        if v > 1:
            del lookup[k]

    return lookup


def get_id_lookup():
    """
    create id lookup from popolo file
    convert datadotparl_id to parlparse
    """
    people_url = "https://github.com/mysociety/parlparse/raw/master/members/people.json"
    pop = Popolo.from_url(people_url)
    count = 0
    lookup = {}
    print("Creating id lookup")
    for p in pop.persons:
        id = p.id
        datadotparl = p.identifier_value("datadotparl_id")
        if datadotparl:
            lookup[datadotparl] = id[-5:]
            count += 1
    print(count, len(pop.persons))
    return lookup


image_format = "https://members-api.parliament.uk/api/Members/{0}/Portrait?CropType=ThreeFour"

def download_and_resize(mp_id, parlparse, override=False):
    """
    download and retrieve the three-four sized
    offical portrait
    """
    filename = "{0}.jpg".format(parlparse)
    alt_filename = "{0}.jpeg".format(parlparse)
    vlarge_path = os.path.join(official_image_folder, filename)
    temp_path = os.path.join(gettempdir(), "{0}.jpg".format(mp_id))
    image_url = image_format.format(id)
    api_url = "https://members-api.parliament.uk/api/Members/{0}".format(mp_id)
    api_results = json.loads(urlopen(api_url).read())
    thumbnail_url = api_results["value"].get("thumbnailUrl", "")
    if "members-api" not in thumbnail_url:
        print("no offical portrait")
        return None
    try:
        urlretrieve(image_url, temp_path)
    except Exception:
        return None
    print("downloaded: {0}".format(image_url))
    try:
        image = Image.open(temp_path)
    except Exception:
        return None
    image.save(vlarge_path, quality=100)
    image.close()
    os.remove(temp_path)


def get_official_images(override=False):
    """
    fetch image if available
    """
    lookup = get_id_lookup()

    for datadotparl, parlparse in lookup.items():
        print(datadotparl, parlparse)
        filename = "{0}.jpg".format(parlparse)
        small_path = os.path.join(small_image_folder, filename)
        large_path = os.path.join(large_image_folder, filename)
        official_path = os.path.join(official_image_folder, filename)
        if exists(official_path) is False or override:
            download_and_resize(datadotparl, parlparse, override)


def ids_from_directory(dir):
    """
    get twfy ids used in images in this dir
    """
    files = os.listdir(dir)
    ids = [os.path.splitext(x)[0] for x in files]
    return set(ids)


def overlap_report():
    """
    quick summary on where we have large images and where
    there are still small images
    """
    
    big = ids_from_directory(official_image_folder)
    wikidata = ids_from_directory(wikidata_image_folder)
    big.update(wikidata)
    small = ids_from_directory(small_image_folder)
    missing = small.difference(big)
    print("There are big images for {0}".format(len(big)))
    print("There are small images for {0}".format(len(small)))
    print("Small but not big: {0}".format(len(missing)))


def pad_to_size(image, size):
    """
    resize images and add padding to get to right size.
    """
    final = Image.new(image.mode, size, "white")
    thumbnail = image.copy()
    thumbnail.thumbnail(size)
    final.paste(thumbnail)
    return final


def downsize(image_path):
    """
    downsize a particular image to the small and big
    folders
    """
    print (image_path)
    path, filename = os.path.split(image_path)
    small_path = os.path.join(small_image_folder, filename)
    large_path = os.path.join(large_image_folder, filename)
    image = Image.open(image_path)
    large_image = pad_to_size(image, (120, 160))
    small_image = pad_to_size(image, (60, 80))
    large_image.save(large_path, quality=95)
    small_image.save(small_path, quality=95)
    image.close()

def make_large_from_folder(folder):
    """
    for each file in folder, create files that match
    small and big size
    """
    for f in os.listdir(folder):
        downsize(os.path.join(folder, f))

def downsize_folders():
    """
    downsize folders
    """
    make_large_from_folder(wikidata_image_folder)
    make_large_from_folder(official_image_folder)

if __name__ == "__main__":
    # get_offical_images()
    # get_wikipedia() # not all of these are usable, need to manually review afterwards
    # get_idless_wikipedia()
    # get_wikidata()
    # downsize_folders()
    overlap_report()
