import { JSX, useState } from "react";
import chroma from 'chroma-js';
import { Loader2 } from 'lucide-react'


class ResultData {
    error?: Error;
    received?: string;
    brandData?: BrandData;
    parsedData?: string;

    constructor(resData: any) {
        resData.error && (this.error = resData.error);
        resData.received && (this.received = resData.received);
        resData.brandData &&
            (this.brandData = new BrandData(resData.brandData));
        resData.parsedData && (this.parsedData = resData.parsedData);
    }
}

class BrandData {
    colors?: ColorData;
    fonts?: FontData;

    constructor(brandData: Record<string, any>) {
        brandData.colors && (this.colors = brandData.colors as ColorData);
        brandData.fonts && (this.fonts = brandData.fonts as FontData);
    }
}

interface ColorData {
    [color: string]: string[];
}

interface FontData {
    [font: string]: string[];
}

const Home = (): JSX.Element => {
    const [input, setInput] = useState("");
    const [loading, setLoading] = useState(false);
    const [resData, setResData] = useState(null);

    const handleSubmit = () => {
        const fetchAddress = "/api/brand_scraper";
        // const fetchAddress = "api/test";

        setLoading(true);
        fetch(fetchAddress, {
            method: "Post",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ url: input }),
        })
            .then((res) => res.json())
            .then((data) => {
                setResData(new ResultData(data) as React.SetStateAction<null>);
                setLoading(false);
            });
    };

    return (
        <div id='main' className="relative h-screen grid grid-rows-[auto_1fr_auto]" >
            <div id='header' className="p-2">
                <h1>Brand Scraper</h1>
            </div>
            {(!resData) ?
                <div id='content-container' className="max-w-md mt-[30vh]">
                    {loading ? <Loading /> : <h2 id="intro" className='text-center'>Scrape a website for its brand colors and fonts.</h2>
                    }


                </div> :
                <div id='content-container' className="pt-10 max-w-2xl w-full">
                    {resData ? <ResultsDisplay resData={resData} /> : null}
                    {loading && <Loading withContent />}
                </div>
            }
            <section id='input-container' className="w-full ">
                <div id='input ' className="mx-auto w-fit p-4">
                    <input
                        className="rounded-tl-sm rounded-bl-sm max-w-sm bg-inputcolor w-screen px-4 py-2 text-lg focus:outline-none"
                        type="text"
                        placeholder="Enter a website URL"
                        onChange={(e) => setInput(e.target.value)}
                    ></input>
                    <button className='rounded-tr-sm rounded-br-sm bg-inputbtncolor px-4 py-2 text-lg text-gray-200 hover:text-white' onClick={handleSubmit}>Submit</button>
                </div>
            </section>
        </div>
    );
};

const Loading = ({ withContent }: { withContent?: boolean }): JSX.Element => {
    return <div id="loading-container" className={"text-center" + (withContent ? " mt-10" : "")}>
        {(!withContent) && <h3 className="mb-4">Parsing Site Content</h3>}
        <Loader2 className="animate-spin mx-auto" size={40} strokeWidth={2} />
    </div>;
};

const ResultsDisplay = ({ resData }: { resData: ResultData }): JSX.Element => {
    return (
        <div className="resultsDisplay">
            {resData.error ? (
                <ErrorDisplay error={resData.error} />
            ) : resData.brandData ? (
                <DataDisplay resData={resData} />
            ) : (
                "No error and no data?!?"
            )}
        </div>
    );
};

const ErrorDisplay = ({ error }: { error: Error }): JSX.Element => {
    return <div className="errorDisplay"><p>{error.toString()}</p></div>;
};

const DataDisplay = ({ resData }: { resData: ResultData }): JSX.Element => {
    return (
        <div id="data-display">
            <h3 className="text-center">Branding for {resData.received}</h3>
            {resData.brandData!.colors ? (
                <ColorDisplay colors={resData.brandData!.colors} />
            ) : null}
            {resData.brandData!.fonts ? (
                <FontDisplay fonts={resData.brandData!.fonts} />
            ) : null}
            {resData.parsedData && (<ParsedDataDisplay parsedData={resData.parsedData} />)}

        </div>
    );
};

const ColorDisplay = ({ colors }: { colors: ColorData }): JSX.Element => {

    return (
        <div id="color-display" className="mt-10">
            <div id='color-container' className="flex flex-wrap justify-space-between gap-8">
                {Object.keys(colors).map((color) => (
                    <ColorPanel key={color} color={color} />
                ))}
            </div>

        </div>
    );
};

const ColorPanel = ({ color }: { color: string }): JSX.Element => {
    const textColor = chroma(color).luminance() > 0.5 ? "black" : "white";
    return (
        <div
            id="color-panel"
            className="rounded-sm flex-1 min-w-[100px] max-w-[200px] aspect-square flex items-center justify-center"
            style={{ backgroundColor: color }}
        >
            <h5 id="color-name" style={{ color: textColor }}>{color}</h5>
        </div>
    )
}

const FontDisplay = ({ fonts }: { fonts: FontData }): JSX.Element => {
    return (
        <div id="font-display" className="mt-10 text-center">
            <h4>
                {"Fonts: " + Object.keys(fonts).join(', ')}
            </h4>
        </div>
    );
};

const ParsedDataDisplay = ({
    parsedData,
    visible
}: {
    parsedData: string;
    visible?: boolean;
}): JSX.Element => {
    return (
        <div id="parsed-data-container" className={visible ? "" : "hidden"}>
            <h3>All Parsed Data</h3>
            <p>{parsedData}</p>
        </div>
    );
};

export default Home;
